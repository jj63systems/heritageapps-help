<?php

namespace HeritageApps\Help\Services;

use HeritageApps\Help\Contracts\AppContextInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIHelperService
{
    public function __construct(
        private DocumentEmbeddingService $embeddingService,
        private ToolRegistry $toolRegistry,
        private ?AppContextInterface $appContext = null,
    ) {}

    /**
     * Ask the AI assistant a question.
     *
     * @param array<int, array{role: string, content: string}> $conversationHistory
     * @return array{success: bool, answer?: string, help_links?: array, error?: string, debug?: array}
     */
    public function ask(
        string $question,
        ?string $currentPage = null,
        ?string $userRole = null,
        array $conversationHistory = []
    ): array {
        if (! $this->canUseAi()) {
            return [
                'success' => false,
                'error'   => 'Access denied. Only administrators can use the AI assistant.',
            ];
        }

        $requestStart = microtime(true);

        $context       = $this->buildContext($question, $currentPage, $userRole);
        $systemPrompt  = $this->buildStaticSystemPrompt();
        $contextMessage = $this->buildContextMessage($context);

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($conversationHistory as $message) {
            $messages[] = ['role' => $message['role'], 'content' => $message['content']];
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $contextMessage . "\n\n" . $question,
        ];

        try {
            $tools              = $this->toolRegistry->getToolSchemas();
            $iteration          = 0;
            $maxIterations      = 5;
            $totalToolCallsMade = 0;
            $anyRetries         = false;
            $totalWaitTime      = 0;
            $timings            = [];
            $data               = null;
            $assistantMessage   = null;

            $timings['php_setup'] = round(microtime(true) - $requestStart, 3);

            while ($iteration < $maxIterations) {
                $iteration++;
                $iterStart = microtime(true);

                $payload = [
                    'model'       => config('help.openai.model'),
                    'messages'    => $messages,
                    'max_tokens'  => (int) config('help.openai.max_tokens'),
                    'temperature' => (float) config('help.openai.temperature'),
                ];

                if (! empty($tools)) {
                    $payload['tools']       = $tools;
                    $payload['tool_choice'] = 'auto';
                }

                $result = $this->callOpenAIWithRetry($payload);

                $timings['openai_' . $iteration] = round(microtime(true) - $iterStart, 3);

                if (! $result['success']) {
                    $response     = $result['response'];
                    $errorDetails = $response->json('error.message', 'Unknown error');

                    Log::error('HeritageApps Help: OpenAI API error', [
                        'iteration' => $iteration,
                        'status'    => $response->status(),
                        'error'     => $errorDetails,
                    ]);

                    return [
                        'success' => false,
                        'error'   => 'Unable to connect to AI service. Please try again later.',
                    ];
                }

                if ($result['retried']) {
                    $anyRetries = true;
                }
                $totalWaitTime += $result['total_wait_time'] ?? 0;

                $data             = $result['response']->json();
                $assistantMessage = $data['choices'][0]['message'];

                if (empty($assistantMessage['tool_calls'])) {
                    break;
                }

                $messages[] = $assistantMessage;
                $totalToolCallsMade += count($assistantMessage['tool_calls']);

                $toolsStart = microtime(true);

                foreach ($assistantMessage['tool_calls'] as $toolCall) {
                    $functionName = $toolCall['function']['name'];
                    $functionArgs = json_decode($toolCall['function']['arguments'], true) ?? [];

                    Log::info('HeritageApps Help: Executing tool', [
                        'function'  => $functionName,
                        'arguments' => $functionArgs,
                    ]);

                    $functionResult = $this->toolRegistry->execute($functionName, $functionArgs);

                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content'      => json_encode($functionResult),
                    ];
                }

                $timings['tools_' . $iteration] = round(microtime(true) - $toolsStart, 3);
            }

            $answer    = $assistantMessage['content'] ?? 'No response generated.';
            $answer    = $this->normalizeAsciiCharts($answer);
            $helpLinks = ($totalToolCallsMade === 0 && ! empty($context['chunks']))
                ? $this->buildHelpLinks($context['chunks'])
                : [];

            $timings['total'] = round(microtime(true) - $requestStart, 3);

            return [
                'success'    => true,
                'answer'     => $answer,
                'help_links' => $helpLinks,
                'usage'      => $data['usage'] ?? null,
                'debug'      => [
                    'system_prompt_length'  => strlen($systemPrompt),
                    'documentation_length'  => strlen($context['documentation']),
                    'current_page'          => $currentPage,
                    'user_role'             => $userRole,
                    'chunks_used'           => count($context['chunks'] ?? []),
                    'tool_calls_made'       => $totalToolCallsMade,
                    'iterations'            => $iteration,
                    'retried'               => $anyRetries,
                    'total_wait_time'       => $totalWaitTime,
                    'timing'                => $timings,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('HeritageApps Help: AI service exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error'   => 'An error occurred while processing your question.',
            ];
        }
    }

    private function canUseAi(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if ($this->appContext) {
            return $this->appContext->canUseAi(auth()->user());
        }

        // Default: allow any authenticated user
        return true;
    }

    private function callOpenAIWithRetry(array $payload, int $maxRetries = 2): array
    {
        $attempt       = 0;
        $retried       = false;
        $totalWaitTime = 0;
        $response      = null;

        while ($attempt <= $maxRetries) {
            $attempt++;

            $response = Http::timeout(config('help.openai.timeout', 60))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('help.openai.key'),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if ($response->successful()) {
                return [
                    'success'         => true,
                    'response'        => $response,
                    'retried'         => $retried,
                    'total_wait_time' => $totalWaitTime,
                ];
            }

            if ($response->status() !== 429 || $attempt > $maxRetries) {
                return ['success' => false, 'response' => $response, 'retried' => $retried];
            }

            $errorMessage = $response->json('error.message', '');
            $waitSeconds  = 10;

            if (preg_match('/try again in ([\d.]+)s/', $errorMessage, $matches)) {
                $waitSeconds = (int) ceil((float) $matches[1]) + 1;
            }

            Log::info('HeritageApps Help: Rate limit, retrying', [
                'attempt'      => $attempt,
                'wait_seconds' => $waitSeconds,
            ]);

            $retried        = true;
            $totalWaitTime += $waitSeconds;
            sleep($waitSeconds);
        }

        return ['success' => false, 'response' => $response, 'retried' => $retried];
    }

    private function buildContext(string $question, ?string $currentPage, ?string $userRole): array
    {
        $isDataQuery  = preg_match('/\b(how many|count|show me|list|who has|who is|which|give me|tell me about|what are the)\b/i', $question);
        $chunkCount   = $isDataQuery
            ? config('help.rag.data_query_chunks', 8)
            : config('help.rag.reference_chunks', 15);

        $relevantChunks = $this->embeddingService->findRelevantChunks($question, $chunkCount);

        if (empty($relevantChunks)) {
            return [
                'user_role'     => $userRole ?? 'unknown',
                'current_page'  => $currentPage ?? 'unknown',
                'documentation' => $this->getFallbackDocumentation(),
                'inventory'     => $this->getInventory(),
                'live_config'   => $this->getLiveConfiguration(),
                'chunks'        => [],
            ];
        }

        $documentation = collect($relevantChunks)->map(function ($chunk) {
            $header = $chunk['section_title'] ? "## {$chunk['section_title']}" : '';
            $source = "Source: {$chunk['file_path']}";
            return "{$header}\n\n{$chunk['content']}\n\n{$source}";
        })->implode("\n\n---\n\n");

        return [
            'user_role'     => $userRole ?? 'unknown',
            'current_page'  => $currentPage ?? 'unknown',
            'documentation' => $documentation,
            'inventory'     => $this->getInventory(),
            'live_config'   => $this->getLiveConfiguration(),
            'chunks'        => $relevantChunks,
        ];
    }

    private function getInventory(): string
    {
        if ($this->appContext) {
            return $this->appContext->getInventory();
        }

        return 'Inventory not configured.';
    }

    private function getLiveConfiguration(): string
    {
        if ($this->appContext) {
            return $this->appContext->getLiveConfiguration();
        }

        return 'Live configuration not configured.';
    }

    private function buildStaticSystemPrompt(): string
    {
        $appName = config('help.app_name', 'management system');

        return <<<PROMPT
You are the AI assistant for this {$appName}. Provide clear, authoritative answers based on the system's capabilities and configuration.

**IMPORTANT**: The user message includes system context (documentation, app structure, configuration). Read this context carefully before answering - it contains live data from the database.

RESPONSE GUIDELINES:

⛔⛔⛔ CRITICAL: NEVER INVENT DATA ⛔⛔⛔
**ABSOLUTE RULE: You MUST call functions for ANY data-related questions. NEVER guess, estimate, or fabricate numbers.**

Before answering with ANY counts, names, dates, or statistics — YOU MUST call the appropriate function FIRST.

⛔⛔⛔ CRITICAL: DATA FORMATTING RULES ⛔⛔⛔
- If returning 2+ data points → ALWAYS use markdown table format
- Single data points can be narrative
- DATES: ALWAYS format as dd/mm/yyyy

NAVIGATION GUIDELINES:
- NEVER reference URLs, routes, or technical paths
- Always describe navigation using UI elements: "Click X menu → then click Y button"

ACCURACY GUIDELINES:
- ONLY describe features explicitly documented — never speculate or add generic advice
- State information with authority; don't say "according to the documentation"
- If a feature isn't in the docs: "I don't see information about that feature"
- NEVER give generic advice like "Test in Staging", "Monitor Logs", "Contact Vendor"

CONTEXT LOCATION: The user message includes:
- Relevant documentation (semantic search results)
- App structure and models
- Live system configuration with actual database data

FOLLOW-UP SUGGESTIONS (OPTIONAL):
Only include follow-up pills if there are genuinely related topics worth exploring.

Format (if including pills):
**You might also want to know:**
- Question one
- Question two
PROMPT;
    }

    private function buildContextMessage(array $context): string
    {
        return <<<CONTEXT
--- SYSTEM CONTEXT (Read carefully before answering) ---

USER CONTEXT:
- Role: {$context['user_role']}
- Current Page: {$context['current_page']}

RELEVANT DOCUMENTATION:
{$context['documentation']}

APPLICATION STRUCTURE:
{$context['inventory']}

LIVE SYSTEM CONFIGURATION:
{$context['live_config']}

--- END CONTEXT ---

CONTEXT;
    }

    private function getFallbackDocumentation(): string
    {
        $docsPath = config('help.docs_path', resource_path('docs'));

        if (! \Illuminate\Support\Facades\File::exists($docsPath)) {
            return 'Documentation not available.';
        }

        $files = collect(\Illuminate\Support\Facades\File::allFiles($docsPath))
            ->filter(fn ($file) => $file->getExtension() === 'md')
            ->sortBy(fn ($file) => $file->getPathname())
            ->values();

        $documentation = [];

        foreach ($files as $file) {
            $relativePath    = str_replace($docsPath . '/', '', $file->getPathname());
            $documentation[] = "=== {$relativePath} ===\n\n" . \Illuminate\Support\Facades\File::get($file->getPathname());
        }

        return ! empty($documentation)
            ? implode("\n\n---\n\n", $documentation)
            : 'Documentation not available.';
    }

    private function buildHelpLinks(array $chunks): array
    {
        $prefix  = config('help.help_url_prefix', '/help');
        $marker  = 'resources/docs/';

        $allMeta = collect(app(DocumentationService::class)->getAllMeta())->keyBy('key');

        return collect($chunks)
            ->map(function ($chunk) use ($marker, $prefix) {
                $pos = strpos($chunk['file_path'], $marker);

                if ($pos === false) {
                    return null;
                }

                $relative = substr($chunk['file_path'], $pos + strlen($marker));
                $key      = str_replace('/', '.', preg_replace('/\.md$/', '', $relative));

                return ['key' => $key, 'url' => $prefix . '/' . $key];
            })
            ->filter()
            ->unique('key')
            ->map(function ($item) use ($allMeta) {
                $meta = $allMeta->get($item['key']);
                return $meta ? ['key' => $item['key'], 'title' => $meta['title']] : null;
            })
            ->filter()
            ->take(3)
            ->values()
            ->toArray();
    }

    private function normalizeAsciiCharts(string $content): string
    {
        $pattern = '/```[a-z]*\n(.*?)\n```/su';

        return preg_replace_callback($pattern, function ($matches) {
            $blockContent = $matches[1];

            if (! preg_match('/[█■▀▄▌▐░▒▓]/u', $blockContent)) {
                return $matches[0];
            }

            $blockContent = trim($blockContent);
            $lines        = explode("\n", $blockContent);
            $chartLines   = [];

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (empty($trimmed)) {
                    continue;
                }

                if (preg_match('/^(.+?):\s*([█■▀▄▌▐░▒▓\s]+?)\s*(\d+)\s*$/u', $trimmed, $match)) {
                    $chartLines[] = [
                        'label' => trim($match[1]),
                        'bar'   => preg_replace('/\s+/', '', $match[2]),
                        'count' => (int) $match[3],
                    ];
                }
            }

            if (count($chartLines) < 2) {
                return $matches[0];
            }

            $maxLabelLength  = max(array_map(fn ($l) => mb_strlen($l['label'] . ':'), $chartLines));
            $barStartColumn  = max(18, $maxLabelLength + 1);
            $rebuiltLines    = [];

            foreach ($chartLines as $chartLine) {
                $labelWithColon = $chartLine['label'] . ':';
                $spacesNeeded   = $barStartColumn - mb_strlen($labelWithColon);
                $rebuiltLines[] = ltrim($labelWithColon . str_repeat(' ', $spacesNeeded) . $chartLine['bar'] . ' ' . $chartLine['count']);
            }

            $chartContent = implode("\n", $rebuiltLines);

            return '<pre style="background: rgb(30 41 59); color: rgb(241 245 249); padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin: 0.5rem 0; font-family: ui-monospace, monospace; font-size: 0.875rem; line-height: 1.5;">' . htmlspecialchars($chartContent, ENT_QUOTES, 'UTF-8') . '</pre>';
        }, $content);
    }
}
