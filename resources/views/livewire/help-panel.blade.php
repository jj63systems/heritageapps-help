<div
    x-data="{ open: @entangle('showPanel') }"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-[60] pointer-events-none"
    x-show="open"
    x-cloak
    style="display: none;"
>
    <!-- Backdrop -->
    <div
        x-transition:enter="transition ease-in duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-out duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm pointer-events-auto"
        @click="open = false"
    ></div>

    <!-- Panel -->
    <div
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 w-full max-w-lg bg-white shadow-lg flex flex-col pointer-events-auto"
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b px-6 py-4 flex-shrink-0">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <h2 class="text-lg font-bold">
                    @if ($notFound)
                        Article Not Found
                    @elseif ($title)
                        {{ $title }}
                    @else
                        Help
                    @endif
                </h2>
            </div>
            <button @click="open = false" type="button">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-6 py-4">
            @if ($notFound)
                <div class="text-center py-12">
                    <p>Help article not yet available for this page.</p>
                    <p class="text-sm mt-2">Browse the Help Centre to find other articles.</p>
                </div>
            @else
                <div class="prose max-w-none">
                    {!! $html !!}
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="border-t px-6 py-4 flex-shrink-0">
            <div class="flex gap-2">
                @if ($docKey && !$notFound)
                    <a
                        href="{{ config('help.help_url_prefix', '/help') }}/{{ $docKey }}"
                        type="button"
                        class="flex-1 px-4 py-2 text-sm font-medium border rounded-lg text-center"
                    >
                        Open in Full View
                    </a>
                @endif

                <button
                    wire:click="closePanel"
                    type="button"
                    class="flex-1 px-4 py-2 text-sm font-medium border rounded-lg"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
