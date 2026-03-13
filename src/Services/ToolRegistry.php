<?php

namespace HeritageApps\Help\Services;

/**
 * Registry for app-specific AI assistant tools (OpenAI function calling).
 *
 * Each app registers its own tools in its HelpServiceProvider::boot():
 *
 *   $registry = app(ToolRegistry::class);
 *   $registry->register('getCatalogueDetails', $schema, fn($args) => [...]);
 *
 * The AIHelperService then calls getToolSchemas() to build the OpenAI payload
 * and execute() to dispatch tool calls.
 */
class ToolRegistry
{
    /** @var array<string, array{schema: array, handler: callable}> */
    private array $tools = [];

    /**
     * Register a tool the AI can call.
     *
     * @param string   $functionName  Unique function name (OpenAI function name)
     * @param array    $schema        OpenAI function calling schema (name, description, parameters)
     * @param callable $handler       Handler: fn(array $args): array
     */
    public function register(string $functionName, array $schema, callable $handler): self
    {
        $this->tools[$functionName] = [
            'schema'  => $schema,
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * Get all registered tools formatted as OpenAI function calling tools array.
     *
     * @return array<int, array{type: string, function: array}>
     */
    public function getToolSchemas(): array
    {
        return array_values(array_map(
            fn ($tool) => ['type' => 'function', 'function' => $tool['schema']],
            $this->tools
        ));
    }

    /**
     * Execute a registered tool by name.
     *
     * @return array<mixed>
     */
    public function execute(string $functionName, array $args): array
    {
        if (! isset($this->tools[$functionName])) {
            return ['error' => "Unknown function: {$functionName}"];
        }

        try {
            return ($this->tools[$functionName]['handler'])($args);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ToolRegistry: tool execution failed', [
                'function' => $functionName,
                'args'     => $args,
                'error'    => $e->getMessage(),
            ]);

            return ['error' => "Tool execution failed: {$e->getMessage()}"];
        }
    }

    public function has(string $functionName): bool
    {
        return isset($this->tools[$functionName]);
    }

    public function count(): int
    {
        return count($this->tools);
    }
}
