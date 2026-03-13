<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 py-8">

        @if ($key && $html)
            {{-- Single article view --}}
            <div class="bg-white rounded-xl shadow-sm p-8">
                <div class="mb-6">
                    <a href="{{ config('help.help_url_prefix', '/help') }}" class="text-sm hover:underline">
                        ← Back to Help Centre
                    </a>
                </div>
                <h1 class="text-2xl font-bold mb-6">{{ $title }}</h1>
                <div class="prose max-w-none">
                    {!! $html !!}
                </div>
            </div>

        @elseif ($notFound)
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                <h1 class="text-xl font-bold mb-2">Article Not Found</h1>
                <p class="text-gray-500">The article you're looking for doesn't exist.</p>
                <a href="{{ config('help.help_url_prefix', '/help') }}" class="mt-4 inline-block text-sm hover:underline">
                    ← Back to Help Centre
                </a>
            </div>

        @else
            {{-- Index view --}}
            <div class="mb-8">
                <h1 class="text-2xl font-bold mb-4">Help Centre</h1>

                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search articles..."
                        class="w-full px-4 py-3 pl-10 border rounded-lg focus:outline-none focus:ring-2"
                    />
                    <svg class="absolute left-3 top-3.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    @if ($search)
                        <button wire:click="clearSearch" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            @foreach ($this->sectionedArticles as $sectionKey => $articles)
                @if (!empty($articles))
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold mb-4 capitalize">
                            {{ ucfirst(str_replace('_', ' ', $sectionKey)) }}
                        </h2>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($articles as $article)
                                <a
                                    href="{{ config('help.help_url_prefix', '/help') }}?key={{ $article['key'] }}"
                                    wire:navigate
                                    class="bg-white rounded-lg p-4 shadow-sm border hover:shadow-md transition"
                                >
                                    <h3 class="font-medium mb-1">{{ $article['title'] }}</h3>
                                    @if (!empty($article['snippet']))
                                        <p class="text-sm text-gray-500 line-clamp-2">{{ $article['snippet'] }}</p>
                                    @endif
                                    <span class="inline-block mt-2 px-2 py-0.5 text-xs bg-gray-100 rounded">
                                        {{ ucfirst($article['doc_type'] ?? 'article') }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if (empty(array_filter($this->sectionedArticles)))
                <div class="text-center py-12">
                    <p class="text-gray-500">No articles found.</p>
                </div>
            @endif
        @endif
    </div>
</div>
