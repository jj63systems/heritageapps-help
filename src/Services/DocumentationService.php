<?php

namespace HeritageApps\Help\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Symfony\Component\Yaml\Yaml;

class DocumentationService
{
    public function __construct(protected MarkdownRenderer $renderer) {}

    /**
     * Resolve a documentation key to its markdown file path.
     */
    public function resolveFile(string $key): ?string
    {
        $docsPath = config('help.docs_path', resource_path('docs'));
        $files = $this->findMarkdownFiles($docsPath);

        foreach ($files as $file) {
            $meta = $this->parseFrontMatter(file_get_contents($file))['meta'];

            if (($meta['key'] ?? null) === $key) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Recursively find all .md files in a directory.
     *
     * @return array<string>
     */
    protected function findMarkdownFiles(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Parse YAML front matter + body from raw markdown string.
     *
     * @return array{meta: array<string, mixed>, body: string}
     */
    public function parseFrontMatter(string $content): array
    {
        $parts = preg_split('/^---\s*$/m', $content, 3);

        if (count($parts) < 3) {
            return ['meta' => [], 'body' => $content];
        }

        $meta = [];

        try {
            $meta = Yaml::parse($parts[1]) ?? [];
        } catch (\Exception) {
            // Continue with empty meta
        }

        return [
            'meta' => is_array($meta) ? $meta : [],
            'body' => trim($parts[2] ?? ''),
        ];
    }

    /**
     * Get rendered HTML + metadata for a doc key.
     * Checks database first (tenant customisations), falls back to markdown file.
     *
     * @return array{title: string, doc_type: string, category: string, html: string}|null
     */
    public function getRendered(string $key, bool $allowDrafts = false): ?array
    {
        $cacheKey = "help.docs.rendered.{$key}" . ($allowDrafts ? '.draft' : '');

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($key, $allowDrafts) {
            $articleClass = config('help.models.article');
            $article = $articleClass::where('key', $key)->first();

            if ($article && ($article->isPublished() || $allowDrafts)) {
                return [
                    'title'    => $article->title,
                    'doc_type' => $article->doc_type,
                    'category' => $this->deriveCategoryFromKey($key, $article->doc_type),
                    'html'     => $article->content,
                ];
            }

            $file = $this->resolveFile($key);

            if (! $file) {
                return null;
            }

            $parsed = $this->parseFrontMatter(file_get_contents($file));
            $meta   = $parsed['meta'];
            $body   = $parsed['body'];

            $docType  = $this->normaliseDocType($meta['doc_type'] ?? null, $key);
            $category = $this->deriveCategoryFromKey($key, $docType);

            return [
                'title'    => $meta['title'] ?? 'Untitled',
                'doc_type' => $docType,
                'category' => $category,
                'html'     => $this->renderer->toHtml($body),
            ];
        });
    }

    /**
     * Get metadata for all docs without rendering bodies.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllMeta(bool $includeDrafts = false): array
    {
        $docsPath = config('help.docs_path', resource_path('docs'));

        $cacheKey  = 'help.docs.all_meta.' . $this->docsSignature($docsPath) . ($includeDrafts ? '.drafts' : '');
        $ttl       = $this->cacheTtl();

        return Cache::remember($cacheKey, $ttl, function () use ($docsPath, $includeDrafts) {
            $allMeta = [];

            foreach ($this->findMarkdownFiles($docsPath) as $file) {
                $parsed = $this->parseFrontMatter(file_get_contents($file));
                $meta   = $parsed['meta'] ?? [];
                $key    = trim((string) ($meta['key'] ?? ''));

                if ($key === '') {
                    continue;
                }

                $docType = $this->normaliseDocType($meta['doc_type'] ?? null, $key);

                $metaData = [
                    'key'               => $key,
                    'title'             => trim((string) ($meta['title'] ?? 'Untitled')),
                    'doc_type'          => $docType,
                    'category'          => $this->deriveCategoryFromKey($key, $docType),
                    'path'              => $file,
                    'audience'          => $meta['audience'] ?? [],
                    'related'           => $meta['related'] ?? [],
                    'search_keywords'   => $meta['search_keywords'] ?? [],
                    'modified'          => filemtime($file),
                    'modified_formatted'=> date('Y-m-d H:i', filemtime($file)),
                    'source'            => 'markdown',
                ];

                $allMeta[$key] = $metaData;
            }

            // Merge database articles (customisations override markdown)
            try {
                $articleClass = config('help.models.article');
                $query = $articleClass::query();

                if (! $includeDrafts) {
                    $query->whereNotNull('published_at');
                }

                if (auth()->check()) {
                    $audiences = $this->resolveAudiences(auth()->user());
                    $query->whereIn('audience', $audiences);
                } else {
                    $query->whereRaw('1=0');
                }

                $dbArticles = $query
                    ->withCount('versions')
                    ->with(['editor', 'versions' => fn ($q) => $q->orderBy('version_number', 'desc')->limit(5)])
                    ->get();

                foreach ($dbArticles as $article) {
                    $allMeta[$article->key] = [
                        'key'               => $article->key,
                        'title'             => $article->title,
                        'doc_type'          => $article->doc_type,
                        'category'          => $this->deriveCategoryFromKey($article->key, $article->doc_type),
                        'path'              => null,
                        'audience'          => [$article->audience],
                        'related'           => [],
                        'search_keywords'   => [],
                        'modified'          => $article->updated_at->timestamp,
                        'modified_formatted'=> $article->updated_at->format('Y-m-d H:i'),
                        'source'            => 'database',
                        'version_count'     => $article->versions_count ?? 0,
                        'latest_versions'   => $article->versions ?? collect(),
                        'is_published'      => $article->isPublished(),
                    ];
                }
            } catch (\Exception) {
                // Table may not exist yet
            }

            $allMeta = array_values($allMeta);
            usort($allMeta, fn ($a, $b) => strcasecmp($a['title'], $b['title']));

            return $allMeta;
        });
    }

    /**
     * Resolve current route name → doc key via config('help.route_map').
     */
    public function resolveKeyForCurrentRoute(): ?string
    {
        $routeName = request()->route()?->getName();

        if (! $routeName) {
            return null;
        }

        return config('help.route_map', [])[$routeName] ?? null;
    }

    /**
     * Full-text search across articles and markdown files.
     *
     * @return array<int, array{key: string, title: string, category: string, snippet: string, doc_type: string}>
     */
    public function search(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $publishedResults = $this->searchPublishedArticles($query, $limit);
        $publishedKeys    = array_column($publishedResults, 'key');
        $remainingLimit   = $limit - count($publishedResults);

        if ($remainingLimit <= 0) {
            return $publishedResults;
        }

        $markdownResults = [];

        try {
            if ($this->tableExists('documentation_index')) {
                $markdownResults = $this->searchDatabase($query, $remainingLimit, $publishedKeys);
            }
        } catch (\Exception) {
            // Fall through
        }

        if (empty($markdownResults)) {
            $markdownResults = $this->searchInMemory($query, $remainingLimit, $publishedKeys);
        }

        return array_merge($publishedResults, $markdownResults);
    }

    protected function searchPublishedArticles(string $query, int $limit): array
    {
        try {
            $driver = DB::connection()->getDriverName();
            $words  = $this->extractSearchWords($query);

            if (empty($words)) {
                return [];
            }

            $articleClass = config('help.models.article');
            $qb = $articleClass::whereNotNull('published_at');

            if (auth()->check()) {
                $audiences = $this->resolveAudiences(auth()->user());
                $qb->whereIn('audience', $audiences);
            } else {
                $qb->whereRaw('1=0');
            }

            if ($driver === 'pgsql') {
                $tsQuery = implode(' | ', array_map(fn ($w) => $w . ':*', $words));
                $qb->whereRaw("to_tsvector('english', title || ' ' || content) @@ to_tsquery('english', ?)", [$tsQuery])
                   ->orderByRaw("ts_rank(to_tsvector('english', title || ' ' || content), to_tsquery('english', ?)) DESC", [$tsQuery]);
            } else {
                $qb->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        $q->orWhere('title', 'LIKE', "%{$word}%")
                          ->orWhere('content', 'LIKE', "%{$word}%");
                    }
                });
            }

            return $qb->limit($limit)->get()->map(function ($article) use ($query) {
                return [
                    'key'      => $article->key,
                    'title'    => $article->title,
                    'category' => $this->deriveCategoryFromKey($article->key, $article->doc_type),
                    'doc_type' => $article->doc_type,
                    'snippet'  => $this->extractSnippet(strip_tags($article->content), $query),
                ];
            })->values()->all();
        } catch (\Exception) {
            return [];
        }
    }

    protected function searchDatabase(string $query, int $limit, array $excludeKeys = []): array
    {
        $driver = DB::connection()->getDriverName();
        $words  = $this->extractSearchWords($query);

        if (empty($words)) {
            return [];
        }

        $qb = DB::table('documentation_index');

        if (! empty($excludeKeys)) {
            $qb->whereNotIn('doc_key', $excludeKeys);
        }

        if ($driver === 'pgsql') {
            $tsQuery = implode(' | ', array_map(fn ($w) => $w . ':*', $words));
            $results = $qb->selectRaw('doc_key, title, category, indexed_body')
                ->whereRaw("search_vector @@ to_tsquery('english', ?)", [$tsQuery])
                ->orderByRaw("ts_rank(search_vector, to_tsquery('english', ?)) DESC", [$tsQuery])
                ->limit($limit)->get();
        } else {
            $results = $qb->selectRaw('doc_key, title, category, indexed_body')
                ->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        $likePattern = "%{$word}%";
                        $q->orWhere(DB::raw('LOWER(title)'), 'LIKE', strtolower($likePattern))
                          ->orWhere(DB::raw('LOWER(indexed_body)'), 'LIKE', strtolower($likePattern));
                    }
                })
                ->limit($limit)->get();
        }

        return $results->map(fn ($result) => [
            'key'      => $result->doc_key,
            'title'    => $result->title,
            'category' => $result->category,
            'doc_type' => $this->getDocType($result->doc_key),
            'snippet'  => $this->extractSnippet($result->indexed_body, $query),
        ])->values()->all();
    }

    protected function searchInMemory(string $query, int $limit, array $excludeKeys = []): array
    {
        $allMeta = $this->getAllMeta();
        $words   = $this->extractSearchWords($query);
        $results = [];

        if (empty($words)) {
            return [];
        }

        foreach ($allMeta as $meta) {
            if (in_array($meta['key'], $excludeKeys, true)) {
                continue;
            }

            $file = $this->resolveFile($meta['key']);

            if (! $file) {
                continue;
            }

            $parsed     = $this->parseFrontMatter(file_get_contents($file));
            $body       = $parsed['body'];
            $titleLower = strtolower($meta['title']);
            $bodyLower  = strtolower($body);
            $matchScore = 0;

            foreach ($words as $word) {
                if (str_contains($titleLower, $word)) {
                    $matchScore += 3;
                }
                if (str_contains($bodyLower, $word)) {
                    $matchScore += 1;
                }
            }

            if ($matchScore > 0) {
                $results[] = [
                    'key'      => $meta['key'],
                    'title'    => $meta['title'],
                    'category' => $meta['category'],
                    'doc_type' => $meta['doc_type'],
                    'snippet'  => $this->extractSnippet($body, $query),
                    'score'    => $matchScore,
                ];
            }
        }

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_map(function ($result) {
            unset($result['score']);
            return $result;
        }, $results), 0, $limit);
    }

    public function clearAllMetaCache(): void
    {
        $docsPath  = config('help.docs_path', resource_path('docs'));
        $signature = $this->docsSignature($docsPath);

        Cache::forget("help.docs.all_meta.{$signature}");
        Cache::forget("help.docs.all_meta.{$signature}.drafts");
    }

    public function getCoreVersion(string $key): ?array
    {
        $file = $this->resolveFile($key);

        if (! $file || ! file_exists($file)) {
            return null;
        }

        $parsed = $this->parseFrontMatter(file_get_contents($file));

        return [
            'title'             => $parsed['meta']['title'] ?? 'Untitled',
            'html'              => $this->renderer->toHtml($parsed['body']),
            'markdown'          => $parsed['body'],
            'doc_type'          => $parsed['meta']['doc_type'] ?? 'reference',
            'modified'          => filemtime($file),
            'modified_formatted'=> date('Y-m-d H:i', filemtime($file)),
        ];
    }

    public function renderMarkdown(string $markdown): string
    {
        return $this->renderer->toHtml($markdown);
    }

    public function getOutdatedCustomizations(): array
    {
        $outdated     = [];
        $articleClass = config('help.models.article');

        foreach ($articleClass::whereNotNull('published_at')->get() as $custom) {
            $coreFile = $this->resolveFile($custom->key);

            if (! $coreFile || ! file_exists($coreFile)) {
                continue;
            }

            $coreMtime   = filemtime($coreFile);
            $customMtime = $custom->updated_at->timestamp;

            if ($coreMtime > $customMtime) {
                $outdated[] = [
                    'key'                    => $custom->key,
                    'title'                  => $custom->title,
                    'core_modified'          => $coreMtime,
                    'core_modified_formatted'=> date('Y-m-d H:i', $coreMtime),
                    'custom_modified'        => $customMtime,
                    'custom_modified_formatted'=> $custom->updated_at->format('Y-m-d H:i'),
                ];
            }
        }

        return $outdated;
    }

    public function getComparison(string $key): ?array
    {
        $articleClass   = config('help.models.article');
        $customArticle  = $articleClass::where('key', $key)->whereNotNull('published_at')->first();

        if (! $customArticle) {
            return null;
        }

        $coreFile = $this->resolveFile($key);

        if (! $coreFile || ! file_exists($coreFile)) {
            return null;
        }

        $coreParsed = $this->parseFrontMatter(file_get_contents($coreFile));
        $coreHtml   = $this->renderer->toHtml($coreParsed['body']);
        $summary    = $this->generateChangeSummary($coreHtml, $customArticle->content);

        return [
            'core' => [
                'title'             => $coreParsed['meta']['title'] ?? 'Untitled',
                'html'              => $this->highlightDifferences($coreHtml, $customArticle->content, true),
                'markdown'          => $coreParsed['body'],
                'modified'          => filemtime($coreFile),
                'modified_formatted'=> date('Y-m-d H:i', filemtime($coreFile)),
            ],
            'custom' => [
                'title'             => $customArticle->title,
                'html'              => $this->highlightDifferences($customArticle->content, $coreHtml, false),
                'modified'          => $customArticle->updated_at->timestamp,
                'modified_formatted'=> $customArticle->updated_at->format('Y-m-d H:i'),
            ],
            'summary' => $summary,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function extractSearchWords(string $query): array
    {
        $words     = preg_split('/[^\p{L}\p{N}]+/u', strtolower($query), -1, PREG_SPLIT_NO_EMPTY);
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];

        $words = array_filter($words, fn ($word) => strlen($word) >= 2 && ! in_array($word, $stopWords, true));

        return array_values(array_unique($words));
    }

    protected function extractSnippet(string $text, string $query): string
    {
        $text = strip_tags($text);

        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        $words = $this->extractSearchWords($query);
        $pos   = false;

        foreach ($words as $word) {
            $pos = mb_stripos($text, $word, 0, 'UTF-8');
            if ($pos !== false) {
                break;
            }
        }

        if ($pos === false) {
            $snippet = mb_substr($text, 0, 150, 'UTF-8');
            return mb_strlen($text, 'UTF-8') > 150 ? $snippet . '...' : $snippet;
        }

        $start   = max(0, $pos - 50);
        $length  = 150;
        $snippet = mb_substr($text, $start, $length, 'UTF-8');

        if ($start > 0) {
            $snippet = '...' . $snippet;
        }

        if ($start + $length < mb_strlen($text, 'UTF-8')) {
            $snippet .= '...';
        }

        return $snippet;
    }

    protected function tableExists(string $table): bool
    {
        try {
            DB::table($table)->limit(0)->count();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    protected function docsSignature(string $docsPath): string
    {
        $parts = [];

        foreach ($this->findMarkdownFiles($docsPath) as $file) {
            $parts[] = $file . '|' . @filemtime($file);
        }

        return sha1(implode("\n", $parts));
    }

    protected function normaliseDocType(?string $docType, string $key): string
    {
        $docType = strtolower(trim((string) $docType));

        if (in_array($docType, ['reference', 'guide', 'role', 'standard', 'faq', 'testing'], true)) {
            return $docType;
        }

        return match (true) {
            str_starts_with($key, 'app.')            => 'reference',
            str_starts_with($key, 'guides.')         => 'guide',
            str_starts_with($key, 'roles.')          => 'role',
            str_starts_with($key, 'standards.')      => 'standard',
            str_starts_with($key, 'data-standards.') => 'standard',
            str_starts_with($key, 'faqs.')           => 'faq',
            str_starts_with($key, 'testing.')        => 'testing',
            default                                  => 'uncategorised',
        };
    }

    protected function deriveCategoryFromKey(string $key, string $docType): string
    {
        return match ($docType) {
            'guide'    => 'guides',
            'role'     => 'roles',
            'standard' => 'data-standards',
            'faq'      => 'faqs',
            'testing'  => 'testing',
            'reference'=> 'app',
            default    => 'uncategorised',
        };
    }

    protected function getDocType(string $key): string
    {
        $article = collect($this->getAllMeta())->firstWhere('key', $key);
        return $article['doc_type'] ?? 'reference';
    }

    protected function cacheTtl(): int
    {
        return app()->isLocal()
            ? config('help.cache.ttl_local_seconds', 5)
            : config('help.cache.ttl_seconds', 3600);
    }

    /**
     * Resolve which audience values a user can see.
     *
     * @return array<string>
     */
    protected function resolveAudiences(\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        $context = $this->resolveAppContext();

        if ($context) {
            return $context->visibleAudienceFor($user);
        }

        // Sensible default: admins see admin + both, others see user + both
        return ['admin', 'both'];
    }

    protected function resolveAppContext(): ?\HeritageApps\Help\Contracts\AppContextInterface
    {
        if (app()->bound(\HeritageApps\Help\Contracts\AppContextInterface::class)) {
            return app(\HeritageApps\Help\Contracts\AppContextInterface::class);
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Comparison / diff helpers (ported from volunteerapp)
    // -------------------------------------------------------------------------

    protected function highlightDifferences(string $primaryHtml, string $compareHtml, bool $isCore): string
    {
        $primaryText = $this->normalizeText($primaryHtml);
        $compareText = $this->normalizeText($compareHtml);

        if ($primaryText === $compareText) {
            return $primaryHtml;
        }

        $dom1 = new \DOMDocument();
        $dom2 = new \DOMDocument();
        $dom1->encoding = 'UTF-8';
        $dom2->encoding = 'UTF-8';

        @$dom1->loadHTML('<?xml encoding="UTF-8"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $primaryHtml . '</body></html>', LIBXML_HTML_NODEFDTD);
        @$dom2->loadHTML('<?xml encoding="UTF-8"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $compareHtml . '</body></html>', LIBXML_HTML_NODEFDTD);

        $xpath1 = new \DOMXPath($dom1);

        $elements1      = $xpath1->query('//p | //div | //h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //li');
        $compareFullText = $this->normalizeText($compareHtml);

        foreach ($elements1 as $element) {
            $text = $this->normalizeText($element->textContent);
            if (empty($text) || strlen($text) < 20) {
                continue;
            }

            if (strpos($compareFullText, $text) === false) {
                $highlightClass = $isCore
                    ? 'bg-green-50 border-l-4 border-green-400 pl-3 rounded'
                    : 'bg-blue-50 border-l-4 border-blue-400 pl-3 rounded';
                $existingClass  = $element->getAttribute('class');
                $element->setAttribute('class', trim($existingClass . ' ' . $highlightClass));
            }
        }

        $html = $dom1->saveHTML($dom1->getElementsByTagName('body')->item(0));
        return preg_replace('~^<body>|</body>$~', '', $html);
    }

    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<[^>]+>/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim(strtolower($text));
    }

    protected function generateChangeSummary(string $coreText, string $customText): array
    {
        $coreNormalized   = $this->normalizeText($coreText);
        $customNormalized = $this->normalizeText($customText);

        preg_match_all('/\b[\p{L}\p{N}]+\b/u', $coreNormalized, $coreMatches);
        preg_match_all('/\b[\p{L}\p{N}]+\b/u', $customNormalized, $customMatches);

        $coreWordArray   = $coreMatches[0] ?? [];
        $customWordArray = $customMatches[0] ?? [];

        $coreWords  = count($coreWordArray);
        $customWords = count($customWordArray);

        preg_match_all('/^#+\s+(.+)$/m', $coreText, $coreHeadings);
        preg_match_all('/^#+\s+(.+)$/m', $customText, $customHeadings);

        $coreHeadingsList   = array_map(fn ($h) => $this->normalizeText($h), $coreHeadings[1] ?? []);
        $customHeadingsList = array_map(fn ($h) => $this->normalizeText($h), $customHeadings[1] ?? []);

        $addedSections   = array_diff($coreHeadingsList, $customHeadingsList);
        $removedSections = array_diff($customHeadingsList, $coreHeadingsList);

        similar_text($coreNormalized, $customNormalized, $percent);

        return [
            'word_count_core'   => $coreWords,
            'word_count_custom' => $customWords,
            'word_diff'         => $coreWords - $customWords,
            'added_sections'    => array_values($addedSections),
            'removed_sections'  => array_values($removedSections),
            'similarity_percent'=> round($percent),
        ];
    }
}
