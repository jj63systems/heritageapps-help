<div
    x-data="{
        open: @entangle('showModal'),
        searchValue: @entangle('search').live
    }"
    @keydown.escape.window="open = false"
    class="fixed inset-0 pointer-events-none"
    style="z-index: 9999;"
    x-show="open"
    x-cloak
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
            class="fixed inset-0 bg-navy-900/50 backdrop-blur-sm pointer-events-auto"
            @click="open = false"
            style="display: none;"
        ></div>

        <!-- Modal -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="transform opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="transform opacity-0 scale-95 translate-y-4"
            class="fixed inset-0 flex items-center justify-center pointer-events-auto px-4"
            style="z-index: 10000;"
        >
            <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full h-[90vh] flex flex-col overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between bg-cream-50 border-b border-cream-200 px-6 py-4 flex-shrink-0">
                    <h2 class="text-xl font-bold text-navy-900 flex items-center gap-3">
                        <svg class="w-6 h-6 text-navy-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Help Centre Library
                    </h2>
                    <button
                        @click="open = false"
                        type="button"
                        class="text-navy-500 hover:text-navy-700 transition"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if (!$editing)
                    <!-- Search -->
                    <div class="bg-cream-50 border-b border-cream-200 px-6 py-4 flex-shrink-0">
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-navy-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                x-model.debounce.150ms="searchValue"
                                type="text"
                                placeholder="Search articles..."
                                autofocus
                                class="w-full pl-10 pr-10 py-2.5 text-sm rounded-lg border border-cream-300 bg-white text-navy-900 placeholder-navy-400 focus:outline-none focus:ring-2 focus:ring-terra-500/20 focus:border-terra-400 transition"
                            />
                            <!-- Clear Search Button -->
                            <button
                                x-show="searchValue && searchValue.length > 0"
                                @click="searchValue = ''"
                                type="button"
                                class="absolute right-3 top-3 text-navy-400 hover:text-navy-600 transition"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-90"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-90"
                                title="Clear search"
                                style="display: none;"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <!-- Loading Spinner -->
                            <div wire:loading class="absolute right-3 top-3">
                                <svg class="animate-spin h-5 w-5 text-terra-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-cream-300">
                            <!-- Base Documentation Mode Buttons (dev only) -->
                            @if (app()->environment('local'))
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2 bg-cream-100 rounded-lg p-1">
                                        <button
                                            wire:click="$set('baseDocumentationMode', false)"
                                            type="button"
                                            class="px-3 py-1.5 text-xs font-medium rounded transition {{ !$baseDocumentationMode ? 'bg-white shadow-sm text-terra-600' : 'text-navy-600 hover:text-navy-900' }}"
                                        >
                                            Tenant Custom
                                        </button>
                                        <button
                                            wire:click="$set('baseDocumentationMode', true)"
                                            type="button"
                                            class="px-3 py-1.5 text-xs font-medium rounded transition {{ $baseDocumentationMode ? 'bg-amber-500 text-white shadow-sm' : 'text-navy-600 hover:text-navy-900' }}"
                                        >
                                            Base Product
                                        </button>
                                    </div>
                                    <span class="text-xs text-navy-600">
                                        @if ($baseDocumentationMode)
                                            <span class="text-amber-700 font-medium">Editing core files (affects all tenants)</span>
                                        @else
                                            <span>Editing tenant customizations</span>
                                        @endif
                                    </span>
                                </div>
                            @else
                                <div></div>
                            @endif

                            <!-- View Switcher and Create Button -->
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2 bg-cream-100 rounded-lg p-1">
                                    <button
                                        wire:click="$set('viewType', 'grid')"
                                        type="button"
                                        class="p-1.5 rounded transition {{ $viewType === 'grid' ? 'bg-white shadow-sm text-terra-600' : 'text-navy-600 hover:text-navy-900' }}"
                                        title="Grid View"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="$set('viewType', 'list')"
                                        type="button"
                                        class="p-1.5 rounded transition {{ $viewType === 'list' ? 'bg-white shadow-sm text-terra-600' : 'text-navy-600 hover:text-navy-900' }}"
                                        title="List View"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Show Drafts Toggle (superadmin only) -->
                                @auth
                                    @if (auth()->user()->isSuperAdmin())
                                        <div class="flex items-center gap-2 px-3 py-1.5 bg-cream-100 rounded-lg">
                                            <span class="text-xs font-medium text-navy-700">Include Drafts</span>
                                            <button
                                                wire:click="toggleShowDrafts"
                                                type="button"
                                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $showDrafts ? 'bg-terra-600' : 'bg-gray-300' }}"
                                                role="switch"
                                                aria-checked="{{ $showDrafts ? 'true' : 'false' }}"
                                            >
                                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform {{ $showDrafts ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                            </button>
                                        </div>
                                    @endif
                                    @if (auth()->user()->isAdmin())
                                        <div class="flex items-center gap-2 px-3 py-1.5 bg-cream-100 rounded-lg">
                                            <span class="text-xs font-medium text-navy-700">Testing Scripts</span>
                                            <button
                                                wire:click="toggleShowTestingScripts"
                                                type="button"
                                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $showTestingScripts ? 'bg-terra-600' : 'bg-gray-300' }}"
                                                role="switch"
                                                aria-checked="{{ $showTestingScripts ? 'true' : 'false' }}"
                                            >
                                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform {{ $showTestingScripts ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                            </button>
                                        </div>
                                    @endif
                                @endauth

                                <!-- Audience Filter -->
                                <div x-data="{ open: false }" class="relative">
                                    <button
                                        @click="open = !open"
                                        type="button"
                                        class="px-3 py-1.5 text-xs font-medium text-navy-700 bg-cream-100 border border-cream-300 rounded-lg hover:bg-cream-50 transition flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                        </svg>
                                        <span>
                                            @if ($audienceFilter === 'all')
                                                All Audiences
                                            @else
                                                {{ ucfirst($audienceFilter) }}
                                            @endif
                                        </span>
                                        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>

                                    <div
                                        x-show="open"
                                        @click.away="open = false"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute z-50 mt-1 w-40 bg-white border border-cream-300 rounded-lg shadow-lg overflow-hidden"
                                        style="display: none;"
                                    >
                                        <div class="py-1">
                                            <button
                                                wire:click="$set('audienceFilter', 'all')"
                                                @click="open = false"
                                                type="button"
                                                class="w-full text-left px-4 py-2 text-xs text-navy-700 hover:bg-terra-50 transition"
                                                :class="{ 'bg-terra-100 font-medium': '{{ $audienceFilter }}' === 'all' }"
                                            >
                                                All Audiences
                                            </button>
                                            <button
                                                wire:click="$set('audienceFilter', 'admin')"
                                                @click="open = false"
                                                type="button"
                                                class="w-full text-left px-4 py-2 text-xs text-navy-700 hover:bg-terra-50 transition"
                                                :class="{ 'bg-terra-100 font-medium': '{{ $audienceFilter }}' === 'admin' }"
                                            >
                                                Admin
                                            </button>
                                            <button
                                                wire:click="$set('audienceFilter', 'volunteer')"
                                                @click="open = false"
                                                type="button"
                                                class="w-full text-left px-4 py-2 text-xs text-navy-700 hover:bg-terra-50 transition"
                                                :class="{ 'bg-terra-100 font-medium': '{{ $audienceFilter }}' === 'volunteer' }"
                                            >
                                                Volunteer
                                            </button>
                                            <button
                                                wire:click="$set('audienceFilter', 'both')"
                                                @click="open = false"
                                                type="button"
                                                class="w-full text-left px-4 py-2 text-xs text-navy-700 hover:bg-terra-50 transition"
                                                :class="{ 'bg-terra-100 font-medium': '{{ $audienceFilter }}' === 'both' }"
                                            >
                                                Both
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Create New Article Button (only in Tenant Custom mode) -->
                                @if (!$baseDocumentationMode)
                                    <button
                                        wire:click="createNewArticle"
                                        type="button"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-terra-600 border border-terra-600 rounded-lg hover:bg-terra-700 transition flex items-center gap-1.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>New Article</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Content -->
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    @if ($showingComparison && $comparisonData)
                        <!-- Comparison View -->
                        <div class="max-w-7xl mx-auto">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-navy-900">Compare Versions</h3>
                                <button
                                    wire:click="closeComparison"
                                    type="button"
                                    class="flex items-center gap-2 text-sm text-navy-600 hover:text-navy-900 transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Close Comparison
                                </button>
                            </div>

                            <!-- Info Banner -->
                            <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm text-amber-900 font-medium">Core documentation has been updated</p>
                                        <p class="text-xs text-amber-800 mt-1">
                                            Core version last modified: <strong>{{ $comparisonData['core']['modified_formatted'] }}</strong><br>
                                            Your custom version last modified: <strong>{{ $comparisonData['custom']['modified_formatted'] }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary of Changes -->
                            @if (isset($comparisonData['summary']))
                                <div class="mb-4 bg-white border border-cream-300 rounded-lg p-4">
                                    <h4 class="text-sm font-bold text-navy-900 mb-3 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-navy-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        Summary of Changes
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                        <div class="text-center p-3 bg-cream-50 rounded">
                                            <div class="text-2xl font-bold text-navy-900">{{ $comparisonData['summary']['similarity_percent'] }}%</div>
                                            <div class="text-xs text-navy-600 mt-1">Similar</div>
                                        </div>
                                        <div class="text-center p-3 bg-cream-50 rounded">
                                            <div class="text-2xl font-bold {{ $comparisonData['summary']['word_diff'] > 0 ? 'text-green-600' : ($comparisonData['summary']['word_diff'] < 0 ? 'text-red-600' : 'text-navy-900') }}">
                                                {{ $comparisonData['summary']['word_diff'] > 0 ? '+' : '' }}{{ $comparisonData['summary']['word_diff'] }}
                                            </div>
                                            <div class="text-xs text-navy-600 mt-1">Word Change</div>
                                        </div>
                                        <div class="text-center p-3 bg-cream-50 rounded">
                                            <div class="text-2xl font-bold text-navy-900">
                                                {{ count($comparisonData['summary']['added_sections']) + count($comparisonData['summary']['removed_sections']) }}
                                            </div>
                                            <div class="text-xs text-navy-600 mt-1">Section Changes</div>
                                        </div>
                                    </div>
                                    @if (count($comparisonData['summary']['added_sections']) > 0 || count($comparisonData['summary']['removed_sections']) > 0)
                                        <div class="text-xs space-y-2">
                                            @if (count($comparisonData['summary']['added_sections']) > 0)
                                                <div>
                                                    <span class="font-medium text-green-700">Added in core:</span>
                                                    <ul class="list-disc list-inside ml-2 text-navy-600">
                                                        @foreach (array_slice($comparisonData['summary']['added_sections'], 0, 3) as $section)
                                                            <li>{{ $section }}</li>
                                                        @endforeach
                                                        @if (count($comparisonData['summary']['added_sections']) > 3)
                                                            <li class="italic">...and {{ count($comparisonData['summary']['added_sections']) - 3 }} more</li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            @endif
                                            @if (count($comparisonData['summary']['removed_sections']) > 0)
                                                <div>
                                                    <span class="font-medium text-red-700">Removed from core:</span>
                                                    <ul class="list-disc list-inside ml-2 text-navy-600">
                                                        @foreach (array_slice($comparisonData['summary']['removed_sections'], 0, 3) as $section)
                                                            <li>{{ $section }}</li>
                                                        @endforeach
                                                        @if (count($comparisonData['summary']['removed_sections']) > 3)
                                                            <li class="italic">...and {{ count($comparisonData['summary']['removed_sections']) - 3 }} more</li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Punctuation Changes -->
                            @if (isset($comparisonData['summary']['punctuation_summary']) && $comparisonData['summary']['punctuation_summary']['has_changes'])
                                <div class="mb-4" x-data="{ punctOpen: false }">
                                    <button
                                        @click="punctOpen = !punctOpen"
                                        type="button"
                                        class="flex items-center gap-2 text-xs text-navy-600 hover:text-navy-900 font-medium transition"
                                    >
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': punctOpen }" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Punctuation Changes ({{ $comparisonData['summary']['punctuation_summary']['total_diff'] > 0 ? '+' : '' }}{{ $comparisonData['summary']['punctuation_summary']['total_diff'] }})
                                    </button>

                                    <div x-show="punctOpen" x-collapse class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs space-y-3">
                                        @if (count($comparisonData['summary']['punctuation_summary']['added']) > 0)
                                            <div>
                                                <div class="font-bold text-green-800 mb-1">Added in Core:</div>
                                                <div class="space-y-1">
                                                    @foreach ($comparisonData['summary']['punctuation_summary']['added'] as $punct)
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-mono bg-white px-2 py-1 rounded border border-green-200 text-green-900">{{ $punct['char'] }}</span>
                                                            <span class="text-navy-700">{{ $punct['display'] }}</span>
                                                            <span class="text-navy-500">({{ $punct['count'] }}×)</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if (count($comparisonData['summary']['punctuation_summary']['removed']) > 0)
                                            <div>
                                                <div class="font-bold text-red-800 mb-1">Removed from Core:</div>
                                                <div class="space-y-1">
                                                    @foreach ($comparisonData['summary']['punctuation_summary']['removed'] as $punct)
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-mono bg-white px-2 py-1 rounded border border-red-200 text-red-900">{{ $punct['char'] }}</span>
                                                            <span class="text-navy-700">{{ $punct['display'] }}</span>
                                                            <span class="text-navy-500">({{ $punct['count'] }}×)</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Debug: Word Differences -->
                            @if (isset($comparisonData['summary']['debug']) && ($comparisonData['summary']['word_diff'] != 0))
                                <div class="mb-4" x-data="{ debugOpen: false }">
                                    <button
                                        @click="debugOpen = !debugOpen"
                                        type="button"
                                        class="flex items-center gap-2 text-xs text-navy-600 hover:text-navy-900 font-medium transition"
                                    >
                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': debugOpen }" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Debug: Show Word Differences ({{ abs($comparisonData['summary']['word_diff']) }} words)
                                    </button>

                                    <div x-show="debugOpen" x-collapse class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-4 text-xs space-y-3">
                                        @if (count($comparisonData['summary']['debug']['words_added_in_core']) > 0)
                                            <div>
                                                <div class="font-bold text-green-800 mb-1">Words added/increased in Core ({{ count($comparisonData['summary']['debug']['words_added_in_core']) }} words):</div>
                                                <div class="font-mono text-green-900 bg-white p-2 rounded border border-green-200">
                                                    {{ implode(', ', $comparisonData['summary']['debug']['words_added_in_core']) }}
                                                </div>
                                            </div>
                                        @endif

                                        @if (count($comparisonData['summary']['debug']['words_removed_from_core']) > 0)
                                            <div>
                                                <div class="font-bold text-red-800 mb-1">Words removed/decreased in Core ({{ count($comparisonData['summary']['debug']['words_removed_from_core']) }} words):</div>
                                                <div class="font-mono text-red-900 bg-white p-2 rounded border border-red-200">
                                                    {{ implode(', ', $comparisonData['summary']['debug']['words_removed_from_core']) }}
                                                </div>
                                            </div>
                                        @endif

                                        <div class="pt-2 border-t border-amber-300">
                                            <div class="font-bold text-navy-800 mb-1">Normalized Text Samples:</div>
                                            <div class="space-y-2">
                                                <div>
                                                    <div class="font-medium text-navy-700">Core (first 200 chars):</div>
                                                    <div class="font-mono text-navy-600 bg-white p-2 rounded border border-cream-300 break-words">
                                                        {{ Str::limit($comparisonData['summary']['debug']['core_normalized'], 200) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-navy-700">Custom (first 200 chars):</div>
                                                    <div class="font-mono text-navy-600 bg-white p-2 rounded border border-cream-300 break-words">
                                                        {{ Str::limit($comparisonData['summary']['debug']['custom_normalized'], 200) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Legend -->
                            <div class="mb-3 flex items-center gap-4 text-xs">
                                <span class="text-navy-600 font-medium">Side-by-Side View:</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-green-50 border-l-2 border-green-400 rounded"></div>
                                    <span class="text-navy-600">Substantially different paragraphs in core</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-blue-50 border-l-2 border-blue-400 rounded"></div>
                                    <span class="text-navy-600">Substantially different paragraphs in custom</span>
                                </div>
                            </div>

                            <!-- Side-by-Side Preview -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                                <!-- Core Version -->
                                <div class="bg-white border border-cream-300 rounded-lg overflow-hidden">
                                    <div class="bg-terra-100 px-4 py-2 border-b border-cream-300">
                                        <h4 class="text-sm font-bold text-navy-900">Core Version (Updated)</h4>
                                        <p class="text-xs text-navy-600">{{ $comparisonData['core']['modified_formatted'] }}</p>
                                    </div>
                                    <div class="p-4 max-h-96 overflow-y-auto">
                                        <div class="docs-prose text-sm space-y-3" @click="let a = $event.target.closest('a'); if (a) { let href = a.getAttribute('href'); if (href && href.startsWith('/admin/help/')) { $event.preventDefault(); $dispatch('open-help-library-article', { key: href.replace('/admin/help/', '') }); } }">
                                            {!! $comparisonData['core']['html'] !!}
                                        </div>
                                    </div>
                                </div>

                                <!-- Your Custom Version -->
                                <div class="bg-white border border-cream-300 rounded-lg overflow-hidden">
                                    <div class="bg-cream-100 px-4 py-2 border-b border-cream-300">
                                        <h4 class="text-sm font-bold text-navy-900">Your Custom Version</h4>
                                        <p class="text-xs text-navy-600">{{ $comparisonData['custom']['modified_formatted'] }}</p>
                                    </div>
                                    <div class="p-4 max-h-96 overflow-y-auto">
                                        <div class="docs-prose text-sm space-y-3" @click="let a = $event.target.closest('a'); if (a) { let href = a.getAttribute('href'); if (href && href.startsWith('/admin/help/')) { $event.preventDefault(); $dispatch('open-help-library-article', { key: href.replace('/admin/help/', '') }); } }">
                                            {!! $comparisonData['custom']['html'] !!}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 flex flex-col sm:flex-row gap-3 justify-end">
                                <button
                                    wire:click="closeComparison(); editArticle()"
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-white bg-terra-600 border border-terra-600 rounded-lg hover:bg-terra-700 transition flex items-center justify-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit My Version
                                </button>
                                <button
                                    wire:click="adoptCoreVersion"
                                    wire:confirm="Are you sure you want to adopt the core version? This will replace your customization with the core version."
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-amber-700 border border-amber-300 rounded-lg hover:bg-amber-50 transition flex items-center justify-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Adopt Core Version
                                </button>
                                <button
                                    wire:click="closeComparison"
                                    type="button"
                                    class="flex items-center gap-2 text-sm text-terra-600 hover:text-terra-700 font-medium transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Back to Article
                                </button>
                            </div>
                        </div>
                    @elseif ($editing)
                        @if ($editing)
                            <!-- Edit Mode -->
                            <div class="max-w-4xl pb-24" x-data="trixEditorModal()" x-id="['trix-edit']">
                                <!-- Header with Back Link -->
                                <div class="mb-6">
                                    <button
                                        wire:click="cancelEdit"
                                        type="button"
                                        class="flex items-center gap-2 text-sm text-terra-600 hover:text-terra-700 font-medium transition mb-4"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                        @if ($editedFromList)
                                            Back to Library
                                        @else
                                            Back to Article
                                        @endif
                                    </button>
                                    <h3 class="text-xl font-bold text-navy-900 mb-1 text-left">
                                        @if ($creatingNew)
                                            Create New Article
                                        @else
                                            Edit Article
                                        @endif
                                    </h3>
                                    <p class="text-sm text-navy-600 text-left">
                                        @if ($creatingNew)
                                            Create a custom documentation article for {{ auth()->user()->isVolunteer() ? 'volunteers' : 'your team' }}
                                        @else
                                            Editing: {{ $editingTitle }}
                                        @endif
                                    </p>
                                </div>

                                <!-- Status Indicator with Publish/Unpublish Button -->
                                <div class="mb-6 pb-4 border-b border-cream-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            @if ($article && $article->isPublished())
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-300">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Published
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-300">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Draft
                                                </span>
                                            @endif
                                        </div>
                                        @if ($article && !$creatingNew)
                                            <button
                                                wire:click="togglePublish"
                                                type="button"
                                                class="px-3 py-1.5 text-xs font-medium text-navy-700 border border-cream-300 rounded-lg hover:bg-cream-50 transition"
                                                wire:loading.attr="disabled"
                                                wire:target="togglePublish"
                                            >
                                                <span wire:loading.remove wire:target="togglePublish">
                                                    @if ($article->isPublished())
                                                        Unpublish
                                                    @else
                                                        Publish
                                                    @endif
                                                </span>
                                                <span wire:loading wire:target="togglePublish">
                                                    Updating...
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if ($editingVersionNumber)
                                    <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <h4 class="text-sm font-bold text-blue-900">Editing from Restored Version</h4>
                                                <p class="text-xs text-blue-800 mt-1">
                                                    This content was restored from <strong>version {{ $editingVersionNumber }}</strong>.
                                                    Saving will create a new current version with this content.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if (app()->environment('local') && !$creatingNew && !$article)
                                <div class="mb-4 flex items-center justify-end">
                                        <!-- Base Documentation Mode Buttons (Dev Only, not shown when creating new or editing custom articles) -->
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-2 bg-cream-100 rounded-lg p-1">
                                                <button
                                                    wire:click="$set('editingBaseDocumentation', false)"
                                                    type="button"
                                                    class="px-3 py-1.5 text-xs font-medium rounded transition {{ !$editingBaseDocumentation ? 'bg-white shadow-sm text-terra-600' : 'text-navy-600 hover:text-navy-900' }}"
                                                >
                                                    Tenant Custom
                                                </button>
                                                <button
                                                    wire:click="$set('editingBaseDocumentation', true)"
                                                    type="button"
                                                    class="px-3 py-1.5 text-xs font-medium rounded transition {{ $editingBaseDocumentation ? 'bg-amber-500 text-white shadow-sm' : 'text-navy-600 hover:text-navy-900' }}"
                                                >
                                                    Base Product
                                                </button>
                                            </div>
                                            <span class="text-xs text-navy-600">
                                                @if ($editingBaseDocumentation)
                                                    <span class="text-amber-700 font-medium">Editing core files (affects all tenants)</span>
                                                @else
                                                    <span>Editing tenant customizations</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                @if ($editingBaseDocumentation && app()->environment('local') && !$creatingNew && !$article)
                                    <!-- Warning Banner for Base Documentation Mode (not shown when creating new or editing custom articles) -->
                                    <div class="mb-4 p-4 bg-amber-50 border-l-4 border-amber-500 rounded">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            <div>
                                                <h4 class="text-sm font-bold text-amber-900 mb-1">Base Product Documentation Mode</h4>
                                                <p class="text-xs text-amber-800">Changes will modify the core markdown files and affect ALL tenants. Images will be saved to <code class="bg-amber-100 px-1 rounded">public/docs/images/</code>. Remember to commit changes to git.</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Article Metadata Collapsible Section -->
                                <div x-data="{ metadataOpen: false }" class="mb-4">
                                    <button
                                        @click="metadataOpen = !metadataOpen"
                                        type="button"
                                        class="w-full flex items-center justify-between p-3 bg-cream-50 border border-cream-200 rounded-lg hover:bg-cream-100 transition"
                                    >
                                        <span class="text-sm font-medium text-navy-700">Article Metadata</span>
                                        <svg
                                            :class="{ 'rotate-180': metadataOpen }"
                                            class="w-5 h-5 text-navy-500 transition-transform"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <div
                                        x-show="metadataOpen"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 -translate-y-2"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-2"
                                        class="mt-2 p-4 border border-cream-200 rounded-lg bg-white"
                                    >
                                        <div class="space-y-4">
                                            <!-- Title -->
                                            <div>
                                                <label class="block text-sm font-medium text-navy-700 mb-2 text-left">Title</label>
                                                <input
                                                    type="text"
                                                    wire:model="editingTitle"
                                                    class="w-full px-4 py-2.5 text-sm rounded-lg border border-cream-300 bg-white text-navy-900 placeholder-navy-400 focus:outline-none focus:ring-2 focus:ring-terra-500/20 focus:border-terra-400 transition"
                                                    placeholder="Article title"
                                                />
                                                @error('editingTitle') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                            </div>

                                            <!-- Type -->
                                            <div x-data="{ open: false }">
                                                <label class="block text-sm font-medium text-navy-700 mb-2 text-left">Type</label>
                                                <div class="relative">
                                                    <button
                                                        @click="open = !open"
                                                        type="button"
                                                        class="w-full px-4 py-2.5 pr-10 text-sm rounded-lg border border-cream-300 bg-white text-navy-900 text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-terra-500/20 focus:border-terra-400 transition hover:bg-cream-50"
                                                    >
                                                <span>
                                                    @if ($editingDocType === 'reference')
                                                        Reference (Screen documentation)
                                                    @elseif ($editingDocType === 'guide')
                                                        Guide (Step-by-step workflow)
                                                    @elseif ($editingDocType === 'role')
                                                        Role (Role overview)
                                                    @elseif ($editingDocType === 'standard')
                                                        Standard (Data standard)
                                                    @elseif ($editingDocType === 'faq')
                                                        FAQ (Frequently asked question)
                                                    @endif
                                                </span>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-navy-500">
                                                    <svg class="h-4 w-4" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </button>

                                            <div
                                                x-show="open"
                                                @click.away="open = false"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="transform opacity-0 scale-95"
                                                x-transition:enter-end="transform opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="transform opacity-100 scale-100"
                                                x-transition:leave-end="transform opacity-0 scale-95"
                                                class="absolute z-50 mt-1 w-full bg-white border border-cream-300 rounded-lg shadow-lg overflow-hidden"
                                                style="display: none;"
                                            >
                                                <div class="py-1">
                                                    <button
                                                        wire:click="$set('editingDocType', 'reference')"
                                                        @click="open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-navy-700 hover:bg-terra-50 transition flex items-center justify-between"
                                                        :class="{ 'bg-terra-100 font-medium': '{{ $editingDocType }}' === 'reference' }"
                                                    >
                                                        <span>Reference (Screen documentation)</span>
                                                        <svg x-show="'{{ $editingDocType }}' === 'reference'" class="w-4 h-4 text-terra-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        wire:click="$set('editingDocType', 'guide')"
                                                        @click="open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-navy-700 hover:bg-terra-50 transition flex items-center justify-between"
                                                        :class="{ 'bg-terra-100 font-medium': '{{ $editingDocType }}' === 'guide' }"
                                                    >
                                                        <span>Guide (Step-by-step workflow)</span>
                                                        <svg x-show="'{{ $editingDocType }}' === 'guide'" class="w-4 h-4 text-terra-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        wire:click="$set('editingDocType', 'role')"
                                                        @click="open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-navy-700 hover:bg-terra-50 transition flex items-center justify-between"
                                                        :class="{ 'bg-terra-100 font-medium': '{{ $editingDocType }}' === 'role' }"
                                                    >
                                                        <span>Role (Role overview)</span>
                                                        <svg x-show="'{{ $editingDocType }}' === 'role'" class="w-4 h-4 text-terra-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        wire:click="$set('editingDocType', 'standard')"
                                                        @click="open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-navy-700 hover:bg-terra-50 transition flex items-center justify-between"
                                                        :class="{ 'bg-terra-100 font-medium': '{{ $editingDocType }}' === 'standard' }"
                                                    >
                                                        <span>Standard (Data standard)</span>
                                                        <svg x-show="'{{ $editingDocType }}' === 'standard'" class="w-4 h-4 text-terra-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        wire:click="$set('editingDocType', 'faq')"
                                                        @click="open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-navy-700 hover:bg-terra-50 transition flex items-center justify-between"
                                                        :class="{ 'bg-terra-100 font-medium': '{{ $editingDocType }}' === 'faq' }"
                                                    >
                                                        <span>FAQ (Frequently asked question)</span>
                                                        <svg x-show="'{{ $editingDocType }}' === 'faq'" class="w-4 h-4 text-terra-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                                @error('editingDocType') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                            </div>

                                            <!-- Audience -->
                                            <div x-data="{ open: false }">
                                                <label class="block text-sm font-medium text-navy-700 mb-2 text-left">Audience</label>
                                                <div class="relative">
                                            <button
                                                @click="open = !open"
                                                type="button"
                                                class="w-full px-4 py-2.5 pr-10 text-sm rounded-lg border border-cream-300 bg-white text-navy-900 text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-terra-500/20 focus:border-terra-400 transition hover:bg-cream-50"
                                            >
                                                <span>
                                                    @if ($editingAudience === 'admin')
                                                        Admin Only (System administrators)
                                                    @elseif ($editingAudience === 'volunteer')
                                                        Volunteer Only (End users)
                                                    @elseif ($editingAudience === 'both')
                                                        Both (All users)
                                                    @endif
                                                </span>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-navy-500">
                                                    <svg class="h-4 w-4" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </button>

                                            <div
                                                x-show="open"
                                                @click.away="open = false"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="transform opacity-0 scale-95"
                                                x-transition:enter-end="transform opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="transform opacity-100 scale-100"
                                                x-transition:leave-end="transform opacity-0 scale-95"
                                                class="absolute z-50 mt-1 w-full bg-white border border-cream-300 rounded-lg shadow-lg overflow-hidden"
                                                style="display: none;"
                                            >
                                                <div class="py-1">
                                                    <button
                                                        wire:click="$set('editingAudience', 'admin')"
                                                        @click="open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-navy-700 hover:bg-terra-50 transition flex items-center justify-between"
                                                        :class="{ 'bg-terra-100 font-medium': '{{ $editingAudience }}' === 'admin' }"
                                                    >
                                                        <span>Admin Only (System administrators)</span>
                                                        <svg x-show="'{{ $editingAudience }}' === 'admin'" class="w-4 h-4 text-terra-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        wire:click="$set('editingAudience', 'volunteer')"
                                                        @click="open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-navy-700 hover:bg-terra-50 transition flex items-center justify-between"
                                                        :class="{ 'bg-terra-100 font-medium': '{{ $editingAudience }}' === 'volunteer' }"
                                                    >
                                                        <span>Volunteer Only (End users)</span>
                                                        <svg x-show="'{{ $editingAudience }}' === 'volunteer'" class="w-4 h-4 text-terra-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        wire:click="$set('editingAudience', 'both')"
                                                        @click="open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm text-navy-700 hover:bg-terra-50 transition flex items-center justify-between"
                                                        :class="{ 'bg-terra-100 font-medium': '{{ $editingAudience }}' === 'both' }"
                                                    >
                                                        <span>Both (All users)</span>
                                                        <svg x-show="'{{ $editingAudience }}' === 'both'" class="w-4 h-4 text-terra-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                                @error('editingAudience') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                                <p class="text-xs text-navy-500 mt-1">Controls who can see this article in search and help</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <!-- Content -->
                                    <div>
                                        <label class="block text-sm font-medium text-navy-700 mb-2 text-left">Content</label>
                                        <div wire:ignore>
                                            <input
                                                id="trix-content-modal"
                                                type="hidden"
                                                name="content"
                                                value="{{ $editingContent }}"
                                            />
                                            <trix-toolbar id="trix-toolbar-modal">
                                                <div class="trix-button-row">
                                                    <span class="trix-button-group trix-button-group--text-tools" data-trix-button-group="text-tools">
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-bold" data-trix-attribute="bold" data-trix-key="b" title="Bold" tabindex="-1">Bold</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-italic" data-trix-attribute="italic" data-trix-key="i" title="Italic" tabindex="-1">Italic</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-strike" data-trix-attribute="strike" title="Strikethrough" tabindex="-1">Strikethrough</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-link" data-trix-attribute="href" data-trix-action="link" data-trix-key="k" title="Link" tabindex="-1">Link</button>
                                                    </span>
                                                    <span class="trix-button-group trix-button-group--block-tools" data-trix-button-group="block-tools">
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-heading-1" data-trix-attribute="heading1" title="Heading" tabindex="-1">H1</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-heading-2" data-trix-attribute="heading2" title="Subheading" tabindex="-1">H2</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-heading-3" data-trix-attribute="heading3" title="Sub-subheading" tabindex="-1">H3</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-quote" data-trix-attribute="quote" title="Quote" tabindex="-1">Quote</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-code" data-trix-attribute="code" title="Code" tabindex="-1">Code</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-bullet-list" data-trix-attribute="bullet" title="Bullets" tabindex="-1">Bullets</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-number-list" data-trix-attribute="number" title="Numbers" tabindex="-1">Numbers</button>
                                                    </span>
                                                    <span class="trix-button-group trix-button-group--file-tools" data-trix-button-group="file-tools">
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-attach" data-trix-action="attachFiles" title="Attach Files" tabindex="-1">Attach Files</button>
                                                    </span>
                                                    <span class="trix-button-group-spacer"></span>
                                                    <span class="trix-button-group trix-button-group--history-tools" data-trix-button-group="history-tools">
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-undo" data-trix-action="undo" data-trix-key="z" title="Undo" tabindex="-1">Undo</button>
                                                        <button type="button" class="trix-button trix-button--icon trix-button--icon-redo" data-trix-action="redo" data-trix-key="shift+z" title="Redo" tabindex="-1">Redo</button>
                                                    </span>
                                                </div>
                                                <div class="trix-dialogs" data-trix-dialogs>
                                                    <div class="trix-dialog trix-dialog--link" data-trix-dialog="href" data-trix-dialog-attribute="href">
                                                        <div class="trix-dialog__link-fields">
                                                            <input type="url" name="href" class="trix-input trix-input--dialog" placeholder="Enter a URL…" aria-label="URL" required data-trix-input>
                                                            <div class="trix-button-group">
                                                                <input type="button" class="trix-button trix-button--dialog" value="Link" data-trix-method="setAttribute">
                                                                <input type="button" class="trix-button trix-button--dialog" value="Unlink" data-trix-method="removeAttribute">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </trix-toolbar>
                                            <trix-editor
                                                input="trix-content-modal"
                                                toolbar="trix-toolbar-modal"
                                                class="trix-content border border-cream-300 rounded-lg min-h-[300px]"
                                            ></trix-editor>
                                        </div>
                                        @error('editingContent') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Change Summary -->
                                    @if ($article)
                                        <div>
                                            <label class="block text-sm font-medium text-navy-700 mb-2">Change Summary (Optional)</label>
                                            <input
                                                type="text"
                                                wire:model="changeSummary"
                                                class="w-full px-4 py-2.5 text-sm rounded-lg border border-cream-300 bg-white text-navy-900 placeholder-navy-400 focus:outline-none focus:ring-2 focus:ring-terra-500/20 focus:border-terra-400 transition"
                                                placeholder="Briefly describe your changes..."
                                            />
                                        </div>
                                    @endif

                                </div>

                                <!-- Fixed Footer with Action Buttons -->
                                <div class="fixed bottom-0 left-0 right-0 bg-cream-50 border-t border-cream-200 px-6 py-4 flex items-center justify-end gap-3" style="z-index: 72;">
                                    <!-- Save & Continue -->
                                    <button
                                        onclick="
                                            const input = document.getElementById('trix-content-modal');
                                            if (input) {
                                                @this.set('editingContent', input.value);
                                                @this.call('saveAndContinue');
                                            }
                                        "
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-navy-700 border border-cream-300 rounded-lg hover:bg-cream-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        wire:loading.attr="disabled"
                                        wire:target="saveAndContinue,saveAndPublish"
                                    >
                                        <span wire:loading.remove wire:target="saveAndContinue">Save & Continue</span>
                                        <span wire:loading wire:target="saveAndContinue">Saving...</span>
                                    </button>

                                    <!-- Save & Close -->
                                    <button
                                        onclick="
                                            const input = document.getElementById('trix-content-modal');
                                            if (input) {
                                                @this.set('editingContent', input.value);
                                                @this.call('saveAndClose');
                                            }
                                        "
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-navy-700 border border-terra-600 rounded-lg hover:bg-terra-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        wire:loading.attr="disabled"
                                        wire:target="saveAndContinue,saveAndClose"
                                    >
                                        <span wire:loading.remove wire:target="saveAndClose">Save & Close</span>
                                        <span wire:loading wire:target="saveAndClose">Saving...</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- List/Table Views -->
                        @if ($viewType === 'list' && ($search === '' || strlen(trim($search)) === 0))
                            <!-- Table View (unified for all modes) -->
                            <div class="bg-white border border-cream-300 rounded-lg overflow-hidden">
                                <table class="w-full">
                                    @if ($showTestingScripts)
                                        <!-- Testing Scripts Table Header -->
                                        <thead class="bg-cream-100 border-b border-cream-300">
                                            <tr>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('name')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Test Script
                                                        @if ($sortBy === 'name')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('test_id')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Test ID
                                                        @if ($sortBy === 'test_id')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('test_name')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Test Name
                                                        @if ($sortBy === 'test_name')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('audience')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Audience
                                                        @if ($sortBy === 'audience')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <span class="text-xs font-semibold text-navy-700 uppercase tracking-wider">Keywords</span>
                                                </th>
                                            </tr>
                                        </thead>
                                    @else
                                        <!-- Regular Documentation Table Header -->
                                        <thead class="bg-cream-100 border-b border-cream-300">
                                            <tr>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('name')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Article
                                                        @if ($sortBy === 'name')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('type')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Type
                                                        @if ($sortBy === 'type')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('status')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Status
                                                        @if ($sortBy === 'status')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('date')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Last Modified
                                                        @if ($sortBy === 'date')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('publication')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Publication
                                                        @if ($sortBy === 'publication')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    <button
                                                        @click.stop="$wire.updateSort('audience')"
                                                        type="button"
                                                        class="flex items-center gap-2 text-xs font-semibold text-navy-700 uppercase tracking-wider hover:text-terra-600 transition cursor-pointer"
                                                    >
                                                        Audience
                                                        @if ($sortBy === 'audience')
                                                            <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </th>
                                                @auth
                                                    @if (auth()->user()->isSuperAdmin())
                                                        @if (!$baseDocumentationMode)
                                                            <th class="px-4 py-3 text-left">
                                                                <span class="text-xs font-semibold text-navy-700 uppercase tracking-wider">Revisions</span>
                                                            </th>
                                                        @endif
                                                        @if ($baseDocumentationMode)
                                                            <th class="px-4 py-3 text-right text-xs font-semibold text-navy-700 uppercase tracking-wider">
                                                                Actions
                                                            </th>
                                                        @endif
                                                    @endif
                                                @endauth
                                            </tr>
                                        </thead>
                                    @endif
                                    <tbody class="bg-white divide-y divide-cream-200" x-data="{ expandedRow: null }">
                                        @foreach ($displayArticles as $article)
                                            @if ($showTestingScripts)
                                                <!-- Testing Scripts Row -->
                                                <tr
                                                    wire:click="viewArticle('{{ $article['key'] }}')"
                                                    class="hover:bg-cream-50 transition cursor-pointer"
                                                    wire:key="table-{{ $article['key'] }}"
                                                >
                                                    <td class="px-4 py-3">
                                                        <div class="text-sm font-medium text-navy-900">
                                                            {{ $article['title'] }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        @if (isset($article['test_id']))
                                                            <span class="text-xs font-mono text-navy-700 font-medium">
                                                                {{ $article['test_id'] }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-navy-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        @if (isset($article['test_name']))
                                                            <span class="text-sm text-navy-700">
                                                                {{ $article['test_name'] }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-navy-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-wrap gap-1">
                                                            @if (isset($article['audience']) && is_array($article['audience']) && count($article['audience']) > 0)
                                                                @foreach ($article['audience'] as $aud)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                                        @if ($aud === 'admin') bg-blue-100 text-blue-800 border border-blue-300
                                                                        @elseif ($aud === 'volunteer') bg-purple-100 text-purple-800 border border-purple-300
                                                                        @elseif ($aud === 'super_admin') bg-indigo-100 text-indigo-800 border border-indigo-300
                                                                        @else bg-teal-100 text-teal-800 border border-teal-300
                                                                        @endif">
                                                                        {{ ucfirst(str_replace('_', ' ', $aud)) }}
                                                                    </span>
                                                                @endforeach
                                                            @else
                                                                <span class="text-xs text-navy-400">—</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-wrap gap-1">
                                                            @if (isset($article['search_keywords']) && is_array($article['search_keywords']) && count($article['search_keywords']) > 0)
                                                                @foreach (array_slice($article['search_keywords'], 0, 3) as $keyword)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                                        {{ $keyword }}
                                                                    </span>
                                                                @endforeach
                                                                @if (count($article['search_keywords']) > 3)
                                                                    <span class="text-xs text-navy-500">+{{ count($article['search_keywords']) - 3 }}</span>
                                                                @endif
                                                            @else
                                                                <span class="text-xs text-navy-400">—</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @else
                                                <!-- Regular Documentation Row -->
                                                <tr
                                                    @if (!$baseDocumentationMode)
                                                        wire:click="viewArticle('{{ $article['key'] }}')"
                                                        class="hover:bg-cream-50 transition cursor-pointer"
                                                    @endif
                                                    wire:key="table-{{ $article['key'] }}"
                                                >
                                                    <td class="px-4 py-3">
                                                        <div class="text-sm font-medium text-navy-900">
                                                            {{ $article['title'] }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="text-sm text-navy-700">{{ ucfirst(str_replace('_', ' ', $article['doc_type'])) }}</span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        @if (collect($outdatedCustomizations)->contains('key', $article['key']))
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-300 w-fit">
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Core Updated
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="text-sm text-navy-700">
                                                            @if (isset($article['updated_at']))
                                                                {{ $article['updated_at'] }}
                                                            @elseif (isset($article['modified_formatted']))
                                                                {{ $article['modified_formatted'] }}
                                                            @else
                                                                —
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        @if (isset($article['is_published']))
                                                            @if ($article['is_published'])
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-300 w-fit">
                                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                    Published
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-300 w-fit">
                                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                    Draft
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-300 w-fit">
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Published
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-wrap gap-1">
                                                            @if (isset($article['audience']) && is_array($article['audience']) && count($article['audience']) > 0)
                                                                @foreach ($article['audience'] as $aud)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                                        @if ($aud === 'admin') bg-blue-100 text-blue-800 border border-blue-300
                                                                        @elseif ($aud === 'volunteer') bg-purple-100 text-purple-800 border border-purple-300
                                                                        @else bg-teal-100 text-teal-800 border border-teal-300
                                                                        @endif">
                                                                        {{ ucfirst($aud) }}
                                                                    </span>
                                                                @endforeach
                                                            @else
                                                                <span class="text-xs text-navy-400">—</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    @auth
                                                        @if (auth()->user()->isSuperAdmin() && !$baseDocumentationMode)
                                                            <td class="px-4 py-3" @click.stop>
                                                                @if (isset($article['version_count']) && $article['version_count'] > 1)
                                                                    <button
                                                                        @click="expandedRow === '{{ $article['key'] }}' ? expandedRow = null : expandedRow = '{{ $article['key'] }}'"
                                                                        type="button"
                                                                        class="flex items-center gap-1 text-sm text-navy-700 hover:text-terra-600 transition"
                                                                    >
                                                                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': expandedRow === '{{ $article['key'] }}' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                        </svg>
                                                                        <span class="font-medium">{{ $article['version_count'] }}</span>
                                                                    </button>
                                                                @else
                                                                    <span class="text-xs text-navy-400">—</span>
                                                                @endif
                                                            </td>
                                                        @endif
                                                        @if (auth()->user()->isSuperAdmin() && app()->environment('local') && $baseDocumentationMode)
                                                            <td class="px-4 py-3 text-right">
                                                                <div class="flex items-center justify-end gap-2">
                                                                    <button
                                                                        wire:click="viewArticle('{{ $article['key'] }}')"
                                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-navy-700 border border-cream-300 rounded hover:bg-cream-50 transition"
                                                                    >
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                        </svg>
                                                                        View
                                                                    </button>
                                                                    <button
                                                                        wire:click="editArticle('{{ $article['key'] }}')"
                                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-terra-600 border border-terra-300 rounded hover:bg-terra-50 transition"
                                                                    >
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                        </svg>
                                                                        Edit
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        @endif
                                                    @endauth
                                                </tr>
                                            @endif

                                            {{-- Expandable Row: Version History --}}
                                            @if (auth()->user()->isSuperAdmin() && isset($article['latest_versions']) && count($article['latest_versions']) > 0)
                                                <tr x-show="expandedRow === '{{ $article['key'] }}'"
                                                    x-transition:enter="transition ease-out duration-200"
                                                    x-transition:enter-start="opacity-0"
                                                    x-transition:enter-end="opacity-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100"
                                                    x-transition:leave-end="opacity-0"
                                                    style="display: none;">
                                                    <td colspan="7" class="px-4 py-4 bg-cream-50 border-t border-cream-200">
                                                        <div class="space-y-2">
                                                            <h4 class="text-sm font-bold text-navy-900 mb-3">Version History</h4>
                                                            @foreach ($article['latest_versions'] as $version)
                                                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-cream-200 hover:border-terra-300 transition">
                                                                    <div class="flex-1">
                                                                        <div class="flex items-center gap-3">
                                                                            <span class="inline-flex items-center px-2 py-1 text-xs font-bold text-white bg-terra-600 rounded">
                                                                                v{{ $version->version_number }}
                                                                            </span>
                                                                            @if ($version->version_number === ($article['version_count'] ?? 0))
                                                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded">
                                                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                                    </svg>
                                                                                    Current
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                        <p class="text-sm text-navy-700 mt-1">
                                                                            {{ $version->change_summary ?? 'No description' }}
                                                                        </p>
                                                                        <p class="text-xs text-navy-500 mt-1">
                                                                            {{ $version->creator->name ?? 'Unknown' }} • {{ $version->created_at->format('d/m/Y H:i') }}
                                                                        </p>
                                                                    </div>
                                                                    <div class="flex items-center gap-2">
                                                                        <button
                                                                            wire:click="viewVersion('{{ $article['key'] }}', {{ $version->version_number }})"
                                                                            type="button"
                                                                            class="px-3 py-1.5 text-xs font-medium text-navy-700 border border-cream-300 rounded-lg hover:bg-cream-50 transition"
                                                                            title="View this version"
                                                                        >
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                            </svg>
                                                                        </button>
                                                                        @if ($version->version_number !== ($article['version_count'] ?? 0))
                                                                            <button
                                                                                wire:click="editVersion('{{ $article['key'] }}', {{ $version->version_number }})"
                                                                                type="button"
                                                                                class="px-3 py-1.5 text-xs font-medium text-terra-700 border border-terra-600 rounded-lg hover:bg-terra-50 transition"
                                                                                title="Restore and edit this version"
                                                                            >
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                                </svg>
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                            @if ($article['version_count'] > 5)
                                                                <p class="text-xs text-navy-500 text-center pt-2">
                                                                    Showing latest 5 of {{ $article['version_count'] }} versions
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <!-- Grouped Articles View (Grid) -->
                            @if ($search === '' || strlen(trim($search)) === 0)
                        @php
                            $grouped = [];
                            foreach ($displayArticles as $article) {
                                $type = $article['doc_type'];
                                if (!isset($grouped[$type])) {
                                    $grouped[$type] = [];
                                }
                                $grouped[$type][] = $article;
                            }

                            $sections = [
                                'reference' => ['title' => 'Screen Reference', 'description' => 'Screen-by-screen guide'],
                                'guide' => ['title' => 'Guides & Workflows', 'description' => 'Step-by-step instructions'],
                                'role' => ['title' => 'Role Overviews', 'description' => 'Learn about your role'],
                                'standard' => ['title' => 'Data Standards', 'description' => 'Rules and conventions'],
                                'faq' => ['title' => 'FAQs', 'description' => 'Common questions answered'],
                                'testing' => ['title' => 'Testing Scripts', 'description' => 'System testing and UAT guides'],
                            ];
                        @endphp

                        @foreach ($sections as $sectionKey => $sectionMeta)
                            @if (isset($grouped[$sectionKey]) && count($grouped[$sectionKey]) > 0)
                                <div class="mb-8">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-bold text-navy-900">{{ $sectionMeta['title'] }}</h3>
                                        <p class="text-sm text-navy-600">{{ $sectionMeta['description'] }}</p>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach ($grouped[$sectionKey] as $article)
                                            <div class="relative" wire:key="grouped-{{ $article['key'] }}">
                                                <button
                                                    wire:click="viewArticle('{{ $article['key'] }}')"
                                                    type="button"
                                                    class="w-full p-4 rounded-lg border border-cream-200 bg-white hover:bg-cream-50 hover:border-terra-300 text-left transition group"
                                                >
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <h4 class="font-medium text-navy-900 group-hover:text-terra-600 transition">{{ $article['title'] }}</h4>
                                                                @if (collect($outdatedCustomizations)->contains('key', $article['key']))
                                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-300">
                                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                                        </svg>
                                                                        Core Updated
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <p class="text-xs text-navy-600 mt-1">{{ ucfirst(str_replace('_', ' ', $article['doc_type'])) }}</p>
                                                        </div>
                                                        <svg class="w-5 h-5 text-navy-400 group-hover:text-terra-600 transition flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </div>
                                                </button>
                                                @auth
                                                    @if (auth()->user()->isSuperAdmin())
                                                        <button
                                                            wire:click="viewArticle('{{ $article['key'] }}'); editArticle()"
                                                            onclick="event.stopPropagation()"
                                                            type="button"
                                                            class="absolute top-2 right-2 p-1.5 rounded bg-white border border-terra-300 text-terra-600 hover:bg-terra-50 opacity-0 group-hover:opacity-100 transition z-10"
                                                            title="Edit"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                @endauth
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                            @else
                                <!-- Search Results View -->
                    @if ($search !== '' && strlen(trim($search)) > 0)
                        @if (count($displayArticles) > 0)
                            <div class="space-y-3">
                                @foreach ($displayArticles as $article)
                                    <div class="relative" wire:key="search-{{ $article['key'] }}">
                                        <button
                                            wire:click="viewArticle('{{ $article['key'] }}')"
                                            type="button"
                                            class="w-full p-4 rounded-lg border border-cream-200 bg-white hover:bg-cream-50 hover:border-terra-300 text-left transition group"
                                        >
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-medium text-navy-900 group-hover:text-terra-600 transition">{!! $this->highlightText($article['title'], $search) !!}</h4>
                                                    <p class="text-xs text-navy-600 mt-1">{{ ucfirst($article['doc_type']) }}</p>
                                                    @if (!empty($article['snippet']))
                                                        <p class="text-sm text-navy-600 mt-2 line-clamp-2">{!! $this->highlightText(Str::limit($article['snippet'], 200), $search) !!}</p>
                                                    @endif
                                                </div>
                                                <svg class="w-5 h-5 text-navy-400 group-hover:text-terra-600 transition flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                        </button>
                                        @auth
                                            @if (auth()->user()->isSuperAdmin())
                                                <button
                                                    wire:click="viewArticle('{{ $article['key'] }}'); editArticle()"
                                                    onclick="event.stopPropagation()"
                                                    type="button"
                                                    class="absolute top-2 right-2 p-1.5 rounded bg-white border border-terra-300 text-terra-600 hover:bg-terra-50 opacity-0 group-hover:opacity-100 transition z-10"
                                                    title="Edit"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        @endauth
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-cream-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10a4 4 0 018 0" />
                                </svg>
                                <p class="text-navy-600 font-medium">No articles found</p>
                                <p class="text-sm text-navy-500 mt-1">Try a different search term</p>
                            </div>
                        @endif
                    @endif
                            @endif
                        @endif
                    @endif
                </div>

                <!-- Footer -->
                <div class="bg-cream-50 border-t border-cream-200 px-6 py-3 flex-shrink-0">
                    @if ($editing)
                        @if ($editingBaseDocumentation && app()->environment('local') && !$article)
                            <!-- Base Documentation Mode Footer (only for markdown files) -->
                            <div class="flex items-center justify-between">
                                <div class="text-xs text-amber-700 font-medium">
                                    Base Product Documentation Mode
                                </div>
                                <button
                                    onclick="
                                        const input = document.getElementById('trix-content-modal');
                                        if (input) {
                                            @this.set('editingContent', input.value);
                                            @this.call('saveToMarkdown');
                                        }
                                    "
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-white bg-amber-600 border border-amber-600 rounded-lg hover:bg-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                    wire:loading.attr="disabled"
                                    wire:target="saveToMarkdown"
                                >
                                    <svg wire:loading wire:target="saveToMarkdown" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="saveToMarkdown">Save to Markdown File</span>
                                    <span wire:loading wire:target="saveToMarkdown">Saving...</span>
                                </button>
                            </div>
                        @endif
                    @else
                        <!-- Normal Footer -->
                        <div class="flex flex-col sm:flex-row gap-2">
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Article Overlay -->
        @if ($viewingArticleKey && !$editing)
            <div
                x-data="{ articleVisible: true }"
                x-show="articleVisible"
                class="fixed inset-0 flex items-center justify-center pointer-events-auto px-4"
                style="z-index: 10001;"
            >
                <!-- Article Backdrop (opaque dim, no blur, so library is hidden not blurred) -->
                <div
                    class="fixed inset-0 bg-navy-900/50"
                    @click="articleVisible = false; $wire.backToList()"
                ></div>

                <!-- Article Card -->
                <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full h-[90vh] flex flex-col overflow-hidden relative">
                    <!-- Article Header -->
                    <div class="flex items-center justify-between bg-cream-50 border-b border-cream-200 px-6 py-4 flex-shrink-0">
                        <h2 class="text-lg font-bold text-navy-900 truncate pr-4">{{ $viewingArticleTitle }}</h2>
                        <button
                            @click="articleVisible = false; $wire.backToList()"
                            type="button"
                            class="text-navy-500 hover:text-navy-700 transition flex-shrink-0"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Article Content -->
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        @if ($viewingVersionNumber)
                            <div class="mb-4 p-3 bg-amber-50 border-l-4 border-amber-500 rounded">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <h4 class="text-sm font-bold text-amber-900">Viewing Historical Version</h4>
                                        <p class="text-xs text-amber-800 mt-1">
                                            You are viewing <strong>version {{ $viewingVersionNumber }}</strong>. This is not the current version.
                                            <button wire:click="viewArticle('{{ $viewingArticleKey }}')" class="underline hover:no-underline">View current version</button>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div
                            class="docs-prose border border-black rounded-md p-4"
                            @click="let a = $event.target.closest('a'); if (a) { let href = a.getAttribute('href'); if (href && href.startsWith('/admin/help/')) { $event.preventDefault(); $dispatch('open-help-library-article', { key: href.replace('/admin/help/', '') }); } }"
                        >
                            {!! $viewingArticleHtml !!}
                        </div>
                    </div>

                    <!-- Article Footer -->
                    <div class="bg-cream-50 border-t border-cream-200 px-6 py-3 flex-shrink-0">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <!-- Email PDF Button -->
                            <button
                                wire:click="emailPdf"
                                type="button"
                                class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-navy-700 border border-cream-300 rounded-lg hover:bg-cream-50 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled"
                                wire:target="emailPdf"
                            >
                                <svg wire:loading.remove wire:target="emailPdf" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <svg wire:loading wire:target="emailPdf" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="emailPdf">Email PDF to Self</span>
                                <span wire:loading wire:target="emailPdf">Sending...</span>
                            </button>

                            @auth
                                @if (auth()->user()->isSuperAdmin())
                                    @if (collect($outdatedCustomizations)->contains('key', $viewingArticleKey))
                                        <button
                                            wire:click="viewComparison('{{ $viewingArticleKey }}')"
                                            type="button"
                                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-300 rounded-lg hover:bg-amber-100 transition flex items-center justify-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                            Compare with Core
                                        </button>
                                    @endif

                                    <button
                                        wire:click="editArticle"
                                        type="button"
                                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-navy-700 border border-cream-300 rounded-lg hover:bg-cream-50 transition flex items-center justify-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit This Article
                                    </button>
                                @endif
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        @endif
</div>

<script>
function trixEditorModal() {
    return {
        saving: false,
        editor: null,

        init() {
            // Store editor reference when component initializes
            this.$nextTick(() => {
                const trixElement = document.querySelector('trix-editor[input="trix-content-modal"]');
                console.log('Trix element:', trixElement);
                if (trixElement) {
                    this.editor = trixElement.editor;
                    console.log('Trix editor initialized:', this.editor);

                    // Manually attach paste handler since @paste doesn't work inside wire:ignore
                    trixElement.addEventListener('paste', (event) => {
                        this.handlePaste(event);
                    });
                    console.log('Paste handler attached');
                } else {
                    console.error('Trix editor element not found');
                }
            });
        },

        syncAndSave() {
            const input = document.getElementById('trix-content-modal');
            if (input) {
                this.saving = true;
                this.$wire.set('editingContent', input.value).then(() => {
                    this.$wire.call('saveArticle').then(() => {
                        this.saving = false;
                    }).catch(() => {
                        this.saving = false;
                    });
                });
            }
        },

        handlePaste(event) {
            console.log('Paste event detected');
            const items = (event.clipboardData || event.originalEvent.clipboardData).items;
            console.log('Clipboard items:', items);

            for (let item of items) {
                console.log('Item type:', item.type);
                if (item.type.indexOf('image') !== -1) {
                    console.log('Image found in clipboard');
                    // Prevent default paste behavior AND stop propagation to avoid double insertion
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();

                    const file = item.getAsFile();
                    console.log('Image file:', file);
                    console.log('Editor reference:', this.editor);

                    if (this.editor) {
                        try {
                            // Create a data URL for immediate preview
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                // Create attachment with data URL for immediate display
                                const attachment = new Trix.Attachment({
                                    url: e.target.result,
                                    contentType: file.type,
                                    filename: file.name,
                                    filesize: file.size
                                });

                                this.editor.insertAttachment(attachment);

                                // Upload the file to server and update URL
                                this.uploadFile(file, attachment);
                            };
                            reader.readAsDataURL(file);
                        } catch (error) {
                            console.error('Error creating/inserting attachment:', error);
                        }
                    } else {
                        // Fallback: try to get editor directly from event
                        const editor = event.target?.editor || document.querySelector('trix-editor')?.editor;
                        if (editor) {
                            this.editor = editor;
                            this.handlePaste(event);
                        }
                    }
                    return false;
                }
            }
        },

        uploadFile(file, attachment) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('baseMode', @this.editingBaseDocumentation ? '1' : '0');

            fetch('/admin/documentation/upload-image', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update attachment with uploaded URL
                    attachment.setAttributes({
                        url: data.url,
                        href: data.url
                    });
                } else {
                    throw new Error('Upload failed');
                }
            })
            .catch(error => {
                console.error('Upload failed:', error);
                attachment.remove();
                if (window.showError) {
                    window.showError('Failed to upload image');
                }
            });
        }
    }
}
</script>
