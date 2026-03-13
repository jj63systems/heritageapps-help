<?php

namespace HeritageApps\Help\Livewire;

use HeritageApps\Help\Mail\AiConversationMail;
use HeritageApps\Help\Services\AIHelperService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class AiHelper extends Component
{
    public bool $open = false;

    public array $messages = [];

    public string $input = '';

    public bool $loading = false;

    public ?string $error = null;

    public ?int $conversationId = null;

    public bool $showDebug = false;

    public array $debugInfo = [];

    protected $listeners = ['openAIHelper' => 'openModal'];

    public function openModal(): void
    {
        $this->open = true;

        $conversationClass   = config('help.models.ai_conversation');
        $latestConversation  = $conversationClass::where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($latestConversation) {
            $this->conversationId = $latestConversation->id;
            $this->messages       = $latestConversation->messages;
        }
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->input))) {
            return;
        }

        $userMessage  = trim($this->input);
        $this->input  = '';

        $this->messages[] = [
            'role'      => 'user',
            'content'   => $userMessage,
            'timestamp' => now()->format('H:i'),
        ];

        $this->saveConversation();

        $this->loading = true;
        $this->error   = null;

        try {
            $startTime           = microtime(true);
            $conversationHistory = array_slice($this->messages, -9, 8);

            $response = app(AIHelperService::class)->ask(
                $userMessage,
                Route::currentRouteName(),
                auth()->user()->role ?? 'unknown',
                $conversationHistory
            );

            $thinkTime     = round(microtime(true) - $startTime, 2);
            $this->loading = false;

            if ($response['success']) {
                $this->messages[] = [
                    'role'       => 'assistant',
                    'content'    => $response['answer'],
                    'timestamp'  => now()->format('H:i'),
                    'think_time' => $thinkTime,
                    'help_links' => $response['help_links'] ?? [],
                ];

                $this->saveConversation();

                if (isset($response['debug']) && $this->isSuperAdmin()) {
                    $this->debugInfo = $response['debug'];
                }
            } else {
                $this->error = $response['error'];
            }
        } catch (\Exception $e) {
            $this->loading = false;
            $this->error   = 'An unexpected error occurred. Please try again.';
            Log::error('HeritageApps Help: AI helper exception', ['error' => $e->getMessage()]);
        }
    }

    public function toggleDebug(): void
    {
        $this->showDebug = ! $this->showDebug;
    }

    public function clearChat(): void
    {
        $this->messages       = [];
        $this->error          = null;
        $this->conversationId = null;
    }

    public function askSuggestion(string $question): void
    {
        $this->input = $question;
        $this->sendMessage();
    }

    public function emailChat(): void
    {
        if (empty($this->messages)) {
            $this->dispatch('showToast', type: 'error', message: 'No conversation to email.');

            return;
        }

        try {
            $pdf      = Pdf::loadView('heritageapps-help::pdf.ai-chat', [
                'messages' => $this->messages,
                'userName' => auth()->user()->name,
                'date'     => now()->format('F j, Y \a\t g:i A'),
            ]);

            $filename = 'ai-conversation-' . now()->format('Y-m-d-His') . '.pdf';
            $tempPath = storage_path('app/temp/' . $filename);

            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $pdf->save($tempPath);

            Mail::to(auth()->user()->email)->send(
                new AiConversationMail(
                    pdfPath: $tempPath,
                    messageCount: count($this->messages)
                )
            );

            $this->dispatch('showToast', type: 'success', message: 'Conversation emailed to ' . auth()->user()->email);

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        } catch (\Exception $e) {
            Log::error('HeritageApps Help: Failed to email AI conversation', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            $this->dispatch('showToast', type: 'error', message: 'Failed to send email: ' . $e->getMessage());

            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    private function saveConversation(): void
    {
        if (empty($this->messages)) {
            return;
        }

        $conversationClass = config('help.models.ai_conversation');

        if ($this->conversationId) {
            $conversationClass::where('id', $this->conversationId)
                ->update(['messages' => $this->messages]);
        } else {
            $conversation         = $conversationClass::create([
                'user_id'  => auth()->id(),
                'messages' => $this->messages,
            ]);
            $this->conversationId = $conversation->id;
        }
    }

    private function isSuperAdmin(): bool
    {
        $user = auth()->user();

        return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    }

    public function render()
    {
        return view('heritageapps-help::livewire.ai-helper');
    }
}
