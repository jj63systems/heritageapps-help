{{-- AI Helper Chat Widget --}}
{{-- Publish this view to customise styling: php artisan vendor:publish --tag=heritageapps-help-views --}}
<div
    x-data="{
        open: @entangle('open'),
        dragging: false,
        resizing: false,
        x: null,
        y: null,
        width: 480,
        height: 560,
        startX: 0,
        startY: 0,
        startWidth: 0,
        startHeight: 0,
        offsetX: 0,
        offsetY: 0,
        initPosition() {
            this.x = window.innerWidth - this.width - 24;
            this.y = window.innerHeight - this.height - 24;
        },
        startDrag(e) {
            this.dragging = true;
            this.offsetX = e.clientX - this.x;
            this.offsetY = e.clientY - this.y;
        },
        onDrag(e) {
            if (!this.dragging) return;
            this.x = Math.max(0, Math.min(window.innerWidth - this.width, e.clientX - this.offsetX));
            this.y = Math.max(0, Math.min(window.innerHeight - this.height, e.clientY - this.offsetY));
        },
        stopDrag() { this.dragging = false; this.resizing = false; }
    }"
    x-init="initPosition()"
    @mousemove.window="onDrag($event)"
    @mouseup.window="stopDrag()"
    @openAIHelper.window="open = true"
    x-show="open"
    x-cloak
    style="display: none; position: fixed; z-index: 9999;"
    :style="`left: ${x}px; top: ${y}px; width: ${width}px; height: ${height}px; z-index: 9999; position: fixed;`"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
>
    <div class="bg-white rounded-xl shadow-2xl border flex flex-col h-full overflow-hidden">
        {{-- Header --}}
        <div
            class="flex items-center justify-between px-4 py-3 border-b bg-gray-50 rounded-t-xl cursor-move select-none flex-shrink-0"
            @mousedown="startDrag($event)"
        >
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                        <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
                    </svg>
                </div>
                <span class="font-semibold text-sm">AI Assistant</span>
            </div>
            <div class="flex items-center gap-1">
                @if (auth()->user() && method_exists(auth()->user(), 'isSuperAdmin') && auth()->user()->isSuperAdmin())
                    <button wire:click="toggleDebug" class="p-1.5 rounded hover:bg-gray-200 transition" title="Toggle debug">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </button>
                @endif
                <button wire:click="clearChat" class="p-1.5 rounded hover:bg-gray-200 transition" title="Clear chat">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <button wire:click="emailChat" class="p-1.5 rounded hover:bg-gray-200 transition" title="Email chat">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </button>
                <button @click="open = false" wire:click="closeModal" class="p-1.5 rounded hover:bg-gray-200 transition" title="Close">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div
            class="flex-1 overflow-y-auto p-4 space-y-4 text-sm"
            id="ai-helper-messages"
            x-data
            x-effect="$el.scrollTop = $el.scrollHeight"
        >
            @if (empty($messages))
                <div class="text-center py-6 text-gray-400">
                    <p class="text-sm">Ask me anything about the system!</p>
                </div>
            @endif

            @foreach ($messages as $message)
                <div class="flex @if($message['role'] === 'user') justify-end @else justify-start @endif">
                    <div class="max-w-[85%] @if($message['role'] === 'user') bg-indigo-600 text-white rounded-2xl rounded-tr-sm @else bg-gray-100 text-gray-900 rounded-2xl rounded-tl-sm @endif px-4 py-2.5">
                        @if ($message['role'] === 'assistant')
                            <div class="prose prose-sm max-w-none">{!! \Illuminate\Support\Str::markdown($message['content']) !!}</div>
                            @if (!empty($message['help_links']))
                                <div class="mt-3 pt-3 border-t border-gray-200 space-y-1">
                                    <p class="text-xs text-gray-500 font-medium">Related articles:</p>
                                    @foreach ($message['help_links'] as $link)
                                        <a href="{{ config('help.help_url_prefix', '/help') }}?key={{ $link['key'] }}" class="block text-xs text-indigo-600 hover:underline">
                                            → {{ $link['title'] }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p>{{ $message['content'] }}</p>
                        @endif
                        <p class="text-xs mt-1 opacity-60">{{ $message['timestamp'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach

            @if ($loading)
                <div class="flex justify-start">
                    <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-2.5">
                        <div class="flex gap-1 items-center">
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                        </div>
                    </div>
                </div>
            @endif

            @if ($error)
                <div class="bg-red-50 text-red-700 rounded-lg px-3 py-2 text-sm">
                    {{ $error }}
                </div>
            @endif
        </div>

        {{-- Debug Panel --}}
        @if ($showDebug && !empty($debugInfo))
            <div class="border-t bg-slate-900 text-slate-100 p-3 text-xs font-mono max-h-32 overflow-y-auto flex-shrink-0">
                <pre>{{ json_encode($debugInfo, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        {{-- Input --}}
        <div class="border-t px-4 py-3 flex-shrink-0">
            <form wire:submit="sendMessage" class="flex gap-2">
                <input
                    type="text"
                    wire:model="input"
                    placeholder="Ask a question..."
                    class="flex-1 text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    @keydown.enter.prevent="$wire.sendMessage()"
                    :disabled="{{ $loading ? 'true' : 'false' }}"
                    wire:loading.attr="disabled"
                />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                >
                    <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <svg wire:loading class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
