<div>
@teleport('body')
    <div
        x-data="{ open: @entangle('showModal') }"
        @open-help-search.window="$wire.openModal()"
        @keydown.escape.window="$wire.closeModal()"
        class="fixed inset-0 z-[70] pointer-events-none"
    >
        <!-- Backdrop -->
        <div
            x-show="open"
            x-transition:enter="transition ease-in duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-out duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm pointer-events-auto"
            @click="$wire.closeModal()"
            style="display: none;"
        ></div>

        <!-- Modal -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="fixed top-20 left-1/2 -translate-x-1/2 w-full max-w-2xl mx-4 bg-white rounded-xl shadow-xl overflow-hidden flex flex-col max-h-[calc(100vh-160px)] pointer-events-auto"
            style="display: none;"
        >
            <!-- Search Input -->
            <div class="border-b p-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="query"
                        placeholder="Search documentation..."
                        class="flex-1 bg-transparent text-sm focus:outline-none"
                        x-init="$el.focus()"
                    />
                    <span class="text-xs px-2 py-1 rounded font-medium">Esc</span>
                </div>
            </div>

            <!-- Results -->
            <div class="flex-1 overflow-y-auto">
                @if (strlen($query) < 2)
                    <div class="p-8 text-center">
                        <p class="text-sm">Type at least 2 characters to search</p>
                    </div>
                @elseif (empty($results))
                    <div class="p-8 text-center">
                        <p class="text-sm">No articles found for "{{ $query }}"</p>
                    </div>
                @else
                    <div class="divide-y">
                        @foreach ($results as $result)
                            <a
                                href="{{ config('help.help_url_prefix', '/help') }}/{{ $result['key'] }}"
                                class="flex items-start gap-3 p-4 hover:bg-gray-50 transition cursor-pointer group"
                                @click="$wire.closeModal()"
                            >
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium mb-1">{{ $result['title'] }}</h3>
                                    <p class="text-xs line-clamp-2 mb-2">{{ $result['snippet'] }}</p>
                                    <span class="inline-block px-2 py-1 bg-gray-100 text-xs font-medium rounded">
                                        {{ ucfirst(str_replace('-', ' ', $result['category'])) }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 flex-shrink-0 group-hover:opacity-100 opacity-40 transition mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="border-t p-4 text-center flex-shrink-0">
                <a href="{{ config('help.help_url_prefix', '/help') }}" class="text-sm font-medium">
                    Browse all articles →
                </a>
            </div>
        </div>
    </div>
@endteleport
</div>
