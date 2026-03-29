<?php

namespace HeritageApps\Help\Livewire;

use HeritageApps\Help\Services\DocumentationService;
use Livewire\Attributes\On;
use Livewire\Component;

class HelpLibraryModal extends Component
{
    #[On('open-help-library')]
    public function openModal(): void
    {
        $this->showModal = true;
        $this->search = '';

        // Reload articles to ensure they're fresh
        $docs = app(DocumentationService::class);
        $this->allArticles = $docs->getAllMeta($this->showDrafts);
        $this->displayArticles = $this->allArticles;

        // Apply filters (including testing scripts filter from session)
        $this->applyFilters();

        $this->viewingArticleKey = null;
        $this->viewingArticleTitle = null;
        $this->viewingArticleHtml = null;
        $this->editing = false;
    }

    #[On('open-help-library-article')]
    public function openArticle(string $key, bool $edit = false): void
    {
        $this->showModal = true;
        $this->viewArticle($key);
        if ($edit && $this->canEditArticles()) {
            $this->editArticle();
        }
    }

    public bool $showModal = false;

    public bool $baseDocumentationMode = false;

    public string $viewType = 'grid'; // 'grid' or 'list'

    public string $sortBy = 'name'; // 'name' or 'date'

    public string $sortDirection = 'asc'; // 'asc' or 'desc'

    public bool $showDrafts = true; // Show unpublished drafts (superadmin only)

    public bool $showTestingScripts = false; // Show testing scripts (admin only)

    public string $audienceFilter = 'all'; // Filter by audience: 'all', 'admin', 'volunteer', 'both'

    public array $fileList = [];

    public bool $editedFromList = false; // Track if user entered edit directly from list

    public string $search = '';

    public array $allArticles = [];

    public array $displayArticles = [];

    public ?string $viewingArticleKey = null;

    public ?string $viewingArticleTitle = null;

    public ?string $viewingArticleHtml = null;

    public ?int $viewingVersionNumber = null;

    // Editing state
    public bool $editing = false;

    public ?int $editingVersionNumber = null;

    public bool $editingBaseDocumentation = false;

    public string $editingTitle = '';

    public string $editingContent = '';

    public string $editingDocType = 'reference';

    public ?string $changeSummary = null;

    public string $editingAudience = 'admin';

    public bool $creatingNew = false;

    public string $editingKey = '';

    public ?object $article = null;

    // Comparison state
    public bool $showingComparison = false;

    public ?string $comparisonKey = null;

    public ?array $comparisonData = null;

    public array $outdatedCustomizations = [];

    protected function canEditArticles(): bool
    {
        $context = app()->bound(\HeritageApps\Help\Contracts\AppContextInterface::class)
            ? app(\HeritageApps\Help\Contracts\AppContextInterface::class)
            : null;

        return $context ? $context->canEditArticles(auth()->user()) : false;
    }

    protected function canViewHelp(): bool
    {
        $context = app()->bound(\HeritageApps\Help\Contracts\AppContextInterface::class)
            ? app(\HeritageApps\Help\Contracts\AppContextInterface::class)
            : null;

        return $context ? $context->canViewHelp(auth()->user()) : true;
    }

    protected function isAdmin(): bool
    {
        $context = app()->bound(\HeritageApps\Help\Contracts\AppContextInterface::class)
            ? app(\HeritageApps\Help\Contracts\AppContextInterface::class)
            : null;

        return $context ? $context->canViewHelp(auth()->user()) : false;
    }

    public function mount(): void
    {
        // Restrict to authenticated users who can view help
        if (!auth()->check() || !$this->canViewHelp()) {
            abort(403);
        }

        // Load view preference from session (for all users)
        $this->viewType = session('help_library_view_type', 'grid');

        // Load sort preferences from session (for all users)
        $this->sortBy = session('help_library_sort_by', 'name');
        $this->sortDirection = session('help_library_sort_direction', 'asc');

        // Load draft visibility preference (can edit only)
        if ($this->canEditArticles()) {
            $this->showDrafts = session('help_library_show_drafts', true);
        }

        // Load testing scripts visibility preference (admin only)
        if ($this->isAdmin()) {
            $this->showTestingScripts = session('help_library_show_testing', false);
        }

        // Load base documentation mode from session (dev only)
        if (app()->environment('local')) {
            $this->baseDocumentationMode = session('base_documentation_mode', false);

            // Load editing preference
            $this->editingBaseDocumentation = session('editing_base_documentation', false);

            // Default to list view when in base documentation mode
            if ($this->baseDocumentationMode) {
                $this->viewType = 'list';
            }
        }

        $docs = app(DocumentationService::class);
        $this->allArticles = $docs->getAllMeta($this->showDrafts);
        $this->displayArticles = $this->allArticles;

        // Apply initial sorting
        $this->sortDisplayArticles();

        // Load outdated customizations (for editors only)
        if ($this->canEditArticles()) {
            $this->outdatedCustomizations = $docs->getOutdatedCustomizations();
        }

        // Initialize file list for base documentation mode
        $this->refreshFileList();
    }

    public function toggleBaseDocumentationMode(): void
    {
        if (!app()->environment('local')) {
            return;
        }

        $this->baseDocumentationMode = !$this->baseDocumentationMode;
        session(['base_documentation_mode' => $this->baseDocumentationMode]);

        // When enabling base mode, switch to list view
        if ($this->baseDocumentationMode) {
            $this->viewType = 'list';
        }

        // Refresh file list when toggling mode
        $this->refreshFileList();

        $this->dispatch('showToast', type: 'info', message: $this->baseDocumentationMode
            ? 'Base Documentation Mode enabled - all edits will modify core markdown files'
            : 'Base Documentation Mode disabled - edits will be tenant-specific');
    }

    public function updateSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        // Save sort preferences to session
        session([
            'help_library_sort_by' => $this->sortBy,
            'help_library_sort_direction' => $this->sortDirection,
        ]);

        // Refresh the file list with new sort order (for base documentation mode)
        $this->refreshFileList();

        // Also sort displayArticles
        $this->sortDisplayArticles();
    }

    public function toggleShowDrafts(): void
    {
        // Only editors can toggle draft visibility
        if (!$this->canEditArticles()) {
            return;
        }

        $this->showDrafts = !$this->showDrafts;
        session(['help_library_show_drafts' => $this->showDrafts]);

        // Reload articles with new filter
        $docs = app(DocumentationService::class);
        $this->allArticles = $docs->getAllMeta($this->showDrafts);

        // Reapply all filters (search, audience, etc.)
        $this->applyFilters();
    }

    public function toggleShowTestingScripts(): void
    {
        // Only admins can toggle testing scripts visibility
        if (!$this->isAdmin()) {
            return;
        }

        $this->showTestingScripts = !$this->showTestingScripts;
        session(['help_library_show_testing' => $this->showTestingScripts]);

        // Reapply all filters (search, audience, testing, etc.)
        $this->applyFilters();
    }

    protected function sortDisplayArticles(): void
    {
        usort($this->displayArticles, function ($a, $b) {
            if ($this->sortBy === 'name') {
                $aVal = $a['title'] ?? '';
                $bVal = $b['title'] ?? '';
                $result = strcasecmp($aVal, $bVal);
            } elseif ($this->sortBy === 'type') {
                $aVal = $a['doc_type'] ?? '';
                $bVal = $b['doc_type'] ?? '';
                $result = strcasecmp($aVal, $bVal);
            } elseif ($this->sortBy === 'status') {
                // Sort by whether article has Core Updated status
                $aIsOutdated = collect($this->outdatedCustomizations)->contains('key', $a['key'] ?? '');
                $bIsOutdated = collect($this->outdatedCustomizations)->contains('key', $b['key'] ?? '');
                $result = ($bIsOutdated <=> $aIsOutdated); // Outdated items first
            } elseif ($this->sortBy === 'date') {
                $aVal = $a['modified'] ?? 0;
                $bVal = $b['modified'] ?? 0;
                $result = $aVal <=> $bVal;
            } elseif ($this->sortBy === 'publication') {
                // Sort by publication status (published first or draft first depending on direction)
                $aPublished = $a['is_published'] ?? true; // Markdown files are always published
                $bPublished = $b['is_published'] ?? true;
                $result = ($bPublished <=> $aPublished); // Published items first
            } elseif ($this->sortBy === 'audience') {
                // Sort by audience alphabetically (join array to string)
                $aVal = is_array($a['audience'] ?? []) ? implode(', ', $a['audience']) : '';
                $bVal = is_array($b['audience'] ?? []) ? implode(', ', $b['audience']) : '';
                $result = strcasecmp($aVal, $bVal);
            } elseif ($this->sortBy === 'test_id') {
                // Sort by test ID (e.g., "SETUP-001")
                $aVal = $a['test_id'] ?? '';
                $bVal = $b['test_id'] ?? '';
                $result = strcasecmp($aVal, $bVal);
            } elseif ($this->sortBy === 'test_name') {
                // Sort by test name alphabetically
                $aVal = $a['test_name'] ?? '';
                $bVal = $b['test_name'] ?? '';
                $result = strcasecmp($aVal, $bVal);
            } else {
                return 0;
            }

            return $this->sortDirection === 'asc' ? $result : -$result;
        });
    }

    protected function refreshFileList(): void
    {
        if (!app()->environment('local') || !$this->baseDocumentationMode) {
            $this->fileList = [];
            return;
        }

        $files = [];
        $docsPath = resource_path('docs');

        // Recursively scan docs directory
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($docsPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $relativePath = str_replace($docsPath . '/', '', $file->getPathname());
                $content = file_get_contents($file->getPathname());

                // Extract key and title from front matter
                preg_match('/^---\n(.*?)\n---/s', $content, $matches);
                if ($matches) {
                    $frontMatter = $matches[1];
                    preg_match('/^key:\s*(.+)$/m', $frontMatter, $keyMatch);
                    preg_match('/^title:\s*(.+)$/m', $frontMatter, $titleMatch);

                    $key = $keyMatch[1] ?? null;
                    $title = $titleMatch[1] ?? basename($file->getFilename(), '.md');

                    if ($key) {
                        $files[] = [
                            'key' => trim($key),
                            'title' => trim($title),
                            'path' => $relativePath,
                            'modified' => $file->getMTime(),
                            'modified_formatted' => date('Y-m-d H:i', $file->getMTime()),
                        ];
                    }
                }
            }
        }

        // Sort files
        usort($files, function ($a, $b) {
            $aVal = $this->sortBy === 'name' ? $a['title'] : $a['modified'];
            $bVal = $this->sortBy === 'name' ? $b['title'] : $b['modified'];

            $result = $this->sortBy === 'name'
                ? strcasecmp($aVal, $bVal)
                : $aVal <=> $bVal;

            return $this->sortDirection === 'asc' ? $result : -$result;
        });

        $this->fileList = $files;
    }

    public function updatedSearch(): void
    {
        $this->applyFilters();
    }

    public function updatedAudienceFilter(): void
    {
        $this->applyFilters();
    }

    protected function applyFilters(): void
    {
        $searchTerm = trim($this->search);

        // Start with all articles or search results
        if ($searchTerm === '') {
            $articles = $this->allArticles;
        } else {
            $docs = app(DocumentationService::class);
            $results = $docs->search($searchTerm, limit: 100);

            $articles = array_map(function ($result) {
                // Find the full article data from allArticles to get dates and test metadata
                $fullArticle = collect($this->allArticles)->firstWhere('key', $result['key']);

                return [
                    'key' => $result['key'],
                    'title' => $result['title'],
                    'category' => $result['category'],
                    'doc_type' => $result['doc_type'],
                    'snippet' => $result['snippet'] ?? '',
                    'modified' => $fullArticle['modified'] ?? 0,
                    'modified_formatted' => $fullArticle['modified_formatted'] ?? '—',
                    'audience' => $fullArticle['audience'] ?? [],
                    'search_keywords' => $fullArticle['search_keywords'] ?? [],
                    'is_published' => $fullArticle['is_published'] ?? true,
                    'version_count' => $fullArticle['version_count'] ?? 0,
                    'latest_versions' => $fullArticle['latest_versions'] ?? collect(),
                    // Test script metadata
                    'test_id' => $fullArticle['test_id'] ?? null,
                    'test_name' => $fullArticle['test_name'] ?? null,
                    'priority' => $fullArticle['priority'] ?? null,
                    'estimated_time' => $fullArticle['estimated_time'] ?? null,
                ];
            }, $results);
        }

        // Apply audience filter
        if ($this->audienceFilter !== 'all') {
            $articles = array_filter($articles, function ($article) {
                $audience = $article['audience'] ?? [];
                if (!is_array($audience)) {
                    return false;
                }
                return in_array($this->audienceFilter, $audience);
            });
        }

        // Testing scripts toggle acts as a mode switch
        if ($this->showTestingScripts) {
            // Show ONLY testing scripts
            $articles = array_filter($articles, function ($article) {
                return ($article['doc_type'] ?? '') === 'testing';
            });
        } else {
            // Hide testing scripts (show everything else)
            $articles = array_filter($articles, function ($article) {
                return ($article['doc_type'] ?? '') !== 'testing';
            });
        }

        $this->displayArticles = $articles;

        if ($searchTerm === '') {
            $this->sortDisplayArticles();
        }
    }

    public function updatedViewType(): void
    {
        // Save view preference to session
        session(['help_library_view_type' => $this->viewType]);
    }

    public function updatedEditingBaseDocumentation(): void
    {
        // Save editing mode preference to session (dev only)
        if (app()->environment('local')) {
            session(['editing_base_documentation' => $this->editingBaseDocumentation]);
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->search = '';
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function highlightText(string $text, string $search): string
    {
        if (empty(trim($search))) {
            return $text;
        }

        $pattern = '/(' . preg_quote($search, '/') . ')/i';
        return preg_replace($pattern, '<mark class="bg-yellow-200 text-navy-900 font-medium">$1</mark>', $text);
    }

    public function viewArticle(string $key): void
    {
        $docs = app(DocumentationService::class);

        // Editors can view drafts
        $allowDrafts = $this->canEditArticles();
        $article = $docs->getRendered($key, $allowDrafts);

        if ($article) {
            $this->viewingArticleKey = $key;
            $this->viewingArticleTitle = $article['title'];
            $this->viewingArticleHtml = $article['html'];
        }
    }

    public function backToList(): void
    {
        $this->viewingArticleKey = null;
        $this->viewingArticleTitle = null;
        $this->viewingArticleHtml = null;
        $this->viewingVersionNumber = null;
        $this->editing = false;
        $this->creatingNew = false;
        $this->editedFromList = false;

        // Reload articles to ensure they're fresh
        $docs = app(DocumentationService::class);
        $this->allArticles = $docs->getAllMeta($this->showDrafts);

        // Apply filters (including testing scripts filter)
        $this->applyFilters();
    }

    public function createNewArticle(): void
    {
        // Only editors can create articles
        abort_unless($this->canEditArticles(), 403);

        $this->creatingNew = true;
        $this->editing = true;
        $this->editedFromList = true;
        $this->editingBaseDocumentation = false; // New articles are always tenant-custom
        $this->viewingArticleKey = null;
        $this->article = null;

        // Reset form fields
        $this->editingTitle = '';
        $this->editingContent = '';
        $this->editingDocType = 'reference';
        $this->editingAudience = 'admin';
        $this->changeSummary = null;
    }

    public function editArticle(?string $key = null): void
    {
        // Only editors can edit
        abort_unless($this->canEditArticles(), 403);

        // If key is provided, set it as the viewing key and mark as edited from list
        if ($key) {
            $this->viewingArticleKey = $key;
            $this->editedFromList = true; // Came directly from list, not from viewing
        } else {
            $this->editedFromList = false; // Came from viewing an article
        }

        if (!$this->viewingArticleKey) {
            return;
        }

        $this->editing = true;
        $this->creatingNew = false;

        // Set editing mode based on current view mode (dev only)
        // If viewing in Base Product mode, edit base docs. If viewing in Tenant mode, edit tenant docs.
        if (app()->environment('local')) {
            $this->editingBaseDocumentation = $this->baseDocumentationMode;
        }

        // Try to load from database first
        $articleClass = config('help.models.article');
        $this->article = $articleClass::where('key', $this->viewingArticleKey)->first();

        if ($this->article) {
            // Load from database
            $this->editingTitle = $this->article->title;
            $this->editingContent = $this->article->content;
            $this->editingDocType = $this->article->doc_type;
            $this->editingAudience = $this->article->audience ?? 'admin';
        } else {
            // Load from markdown file and convert to HTML for WYSIWYG editing
            $docs = app(DocumentationService::class);
            // Editors can always access any article
            $rendered = $docs->getRendered($this->viewingArticleKey, true);

            if ($rendered) {
                $this->editingTitle = $rendered['title'];
                // Ensure proper spacing for Trix editor
                $this->editingContent = $this->ensureTrixSpacing($rendered['html']);
                $this->editingDocType = $rendered['doc_type'];
                $this->editingAudience = 'admin'; // Default for core documents
            }
        }

        $this->changeSummary = null;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->editingVersionNumber = null;

        // If user came directly from list, return to list
        if ($this->editedFromList) {
            $this->backToList();
        } else {
            // Otherwise reload the article view
            $this->viewArticle($this->viewingArticleKey);
        }
    }

    public function viewVersion(string $key, int $versionNumber): void
    {
        $articleClass = config('help.models.article');
        $article = $articleClass::where('key', $key)->first();
        if (!$article) {
            return;
        }

        $version = $article->getVersion($versionNumber);
        if (!$version) {
            return;
        }

        $this->viewingArticleKey = $key;
        $this->viewingVersionNumber = $versionNumber;

        // Render the version content (not current article content)
        $docs = app(DocumentationService::class);
        $html = $docs->renderMarkdown($version->content);

        $this->viewingArticleTitle = $version->title;
        $this->viewingArticleHtml = $html;
    }

    public function editVersion(string $key, int $versionNumber): void
    {
        // Only editors can edit
        abort_unless($this->canEditArticles(), 403);

        $articleClass = config('help.models.article');
        $article = $articleClass::where('key', $key)->first();
        if (!$article) {
            return;
        }

        $version = $article->getVersion($versionNumber);
        if (!$version) {
            return;
        }

        // Restore this version to current (creates a new version entry)
        $article->restoreVersion($versionNumber, "Restored from version {$versionNumber}");

        // Now load the article for editing (it now has the old version's content)
        $this->editArticle($key);
        $this->editingVersionNumber = $versionNumber; // Track that we're editing from a restored version
    }

    public function saveAndContinue(): void
    {
        // Only editors can edit
        abort_unless($this->canEditArticles(), 403);

        $this->validate([
            'editingTitle' => 'required|string|max:255',
            'editingContent' => 'required|string',
            'editingDocType' => 'required|in:reference,guide,role,standard,faq,testing',
            'editingAudience' => 'required|in:admin,volunteer,both',
        ]);

        // Auto-generate key for new articles from title
        if ($this->creatingNew) {
            $baseKey = 'custom.' . \Illuminate\Support\Str::slug($this->editingTitle);
            $articleKey = $baseKey;

            // Check for uniqueness and append number if needed
            $counter = 2;
            $articleClass = config('help.models.article');
            while ($articleClass::where('key', $articleKey)->exists()) {
                $articleKey = $baseKey . '-' . $counter;
                $counter++;
            }

            $this->viewingArticleKey = $articleKey;
        }

        if (!$this->viewingArticleKey) {
            $this->addError('editingTitle', 'Unable to generate article key from title');

            return;
        }

        // Create or update article
        if ($this->article) {
            // Check if content actually changed
            if ($this->article->title === $this->editingTitle && $this->article->content === $this->editingContent) {
                $this->dispatch('showToast', type: 'info', message: 'No changes to save');

                return;
            }

            $this->article->update([
                'title' => $this->editingTitle,
                'content' => $this->editingContent,
                'doc_type' => $this->editingDocType,
                'audience' => $this->editingAudience,
                'edited_by' => auth()->id(),
            ]);

            // Create version record
            $this->article->createVersion(
                $this->changeSummary ?: 'Content updated',
                auth()->id()
            );
        } else {
            // Create new article
            $articleClass = config('help.models.article');
            $this->article = $articleClass::create([
                'key' => $this->viewingArticleKey,
                'title' => $this->editingTitle,
                'content' => $this->editingContent,
                'doc_type' => $this->editingDocType,
                'audience' => $this->editingAudience,
                'edited_by' => auth()->id(),
            ]);

            // Create initial version
            $this->article->createVersion('Article created', auth()->id());

            // Mark as no longer creating new since we've now created it
            $this->creatingNew = false;
        }

        // Clear cache so changes appear immediately (both regular and draft cache)
        \Illuminate\Support\Facades\Cache::forget("docs.rendered.{$this->viewingArticleKey}");
        \Illuminate\Support\Facades\Cache::forget("docs.rendered.{$this->viewingArticleKey}.draft");

        // Clear article list cache so new articles appear in the list
        $docs = app(DocumentationService::class);
        $docs->clearAllMetaCache();

        // Stay in edit mode
        $this->dispatch('showToast', type: 'success', message: 'Draft saved');
    }

    public function togglePublish(): void
    {
        // Only editors can publish/unpublish
        abort_unless($this->canEditArticles(), 403);

        if (!$this->article) {
            return;
        }

        if ($this->article->isPublished()) {
            $this->article->update(['published_at' => null]);
            $this->dispatch('showToast', type: 'success', message: 'Article unpublished');
        } else {
            $this->article->update(['published_at' => now()]);
            $this->dispatch('showToast', type: 'success', message: 'Article published');
        }

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget("docs.rendered.{$this->viewingArticleKey}");
        \Illuminate\Support\Facades\Cache::forget("docs.rendered.{$this->viewingArticleKey}.draft");

        // Clear article list cache
        $docs = app(DocumentationService::class);
        $docs->clearAllMetaCache();

        // Refresh article list
        $this->allArticles = $docs->getAllMeta($this->showDrafts);
        $this->displayArticles = $this->allArticles;
        $this->sortDisplayArticles();
    }

    public function saveAndClose(): void
    {
        // Only editors can edit
        abort_unless($this->canEditArticles(), 403);

        $rules = [
            'editingTitle' => 'required|string|max:255',
            'editingContent' => 'required|string',
            'editingDocType' => 'required|in:reference,guide,role,standard,faq,testing',
            'editingAudience' => 'required|in:admin,volunteer,both',
        ];

        $this->validate($rules);

        // Auto-generate key for new articles from title
        if ($this->creatingNew) {
            $baseKey = 'custom.' . \Illuminate\Support\Str::slug($this->editingTitle);
            $articleKey = $baseKey;

            // Check for uniqueness and append number if needed
            $counter = 2;
            $articleClass = config('help.models.article');
            while ($articleClass::where('key', $articleKey)->exists()) {
                $articleKey = $baseKey . '-' . $counter;
                $counter++;
            }
        } else {
            $articleKey = $this->viewingArticleKey;
        }

        if (!$articleKey) {
            $this->addError('editingTitle', 'Unable to generate article key from title');
            return;
        }

        // Create or update article
        if ($this->article) {
            $this->article->update([
                'title' => $this->editingTitle,
                'content' => $this->editingContent,
                'doc_type' => $this->editingDocType,
                'audience' => $this->editingAudience,
                'edited_by' => auth()->id(),
            ]);

            // Create version record
            $this->article->createVersion(
                $this->changeSummary ?: 'Content updated',
                auth()->id()
            );
        } else {
            // Create new article
            $articleClass = config('help.models.article');
            $this->article = $articleClass::create([
                'key' => $articleKey,
                'title' => $this->editingTitle,
                'content' => $this->editingContent,
                'doc_type' => $this->editingDocType,
                'audience' => $this->editingAudience,
                'edited_by' => auth()->id(),
            ]);

            // Create initial version
            $this->article->createVersion('Article created', auth()->id());

            // Set viewingArticleKey for newly created articles
            $this->viewingArticleKey = $articleKey;
        }

        // Clear cache - both the rendered article and the article list (regular and draft)
        \Illuminate\Support\Facades\Cache::forget("docs.rendered.{$this->viewingArticleKey}");
        \Illuminate\Support\Facades\Cache::forget("docs.rendered.{$this->viewingArticleKey}.draft");

        // Clear article list cache so new articles appear immediately
        $docs = app(DocumentationService::class);
        $docs->clearAllMetaCache();

        // Refresh article list
        $docs = app(DocumentationService::class);
        $this->allArticles = $docs->getAllMeta($this->showDrafts);
        $this->displayArticles = $this->allArticles;
        $this->sortDisplayArticles();

        // Refresh outdated customizations list
        if ($this->canEditArticles()) {
            $this->outdatedCustomizations = $docs->getOutdatedCustomizations();
        }

        // If currently viewing comparison for this article, reload it with fresh data
        if ($this->showingComparison && $this->comparisonKey === $this->viewingArticleKey) {
            $this->comparisonData = $docs->getComparison($this->viewingArticleKey);
        }

        // Return to library list (keep modal open)
        $this->backToList();

        $this->dispatch('showToast', type: 'success', message: 'Article published successfully');
    }

    /**
     * Save base documentation to markdown file (dev only).
     */
    public function saveToMarkdown(): void
    {
        // Only allow in local environment
        if (!app()->environment('local')) {
            $this->dispatch('showToast', type: 'error', message: 'Base documentation editing only available in development');
            return;
        }

        // Only editors
        abort_unless($this->canEditArticles(), 403);

        $this->validate([
            'editingTitle' => 'required|string|max:255',
            'editingContent' => 'required|string',
            'editingDocType' => 'required|in:reference,guide,role,standard,faq',
        ]);

        if (!$this->viewingArticleKey) {
            $this->addError('viewingArticleKey', 'Please select an article first');
            return;
        }

        try {
            // Find the markdown file
            $docs = app(DocumentationService::class);
            $filePath = $docs->resolveFile($this->viewingArticleKey);

            if (!$filePath) {
                $this->dispatch('showToast', type: 'error', message: 'Markdown file not found for this article');
                return;
            }

            // Read existing file to preserve front matter
            $existingContent = file_get_contents($filePath);
            preg_match('/^---\n(.*?)\n---\n/s', $existingContent, $matches);

            if (!$matches) {
                $this->dispatch('showToast', type: 'error', message: 'Invalid markdown file format');
                return;
            }

            $frontMatter = $matches[1];

            // Update front matter fields
            $frontMatter = preg_replace('/^title:.*$/m', "title: {$this->editingTitle}", $frontMatter);
            $frontMatter = preg_replace('/^doc_type:.*$/m', "doc_type: {$this->editingDocType}", $frontMatter);

            // Convert HTML to Markdown
            $markdown = $this->convertHtmlToMarkdown($this->editingContent);

            // Write file
            $newContent = "---\n{$frontMatter}\n---\n\n{$markdown}";
            file_put_contents($filePath, $newContent);

            // Clear cache
            \Illuminate\Support\Facades\Cache::forget("docs.rendered.{$this->viewingArticleKey}");

            $this->dispatch('showToast', type: 'success', message: 'Base documentation saved to markdown file');
        } catch (\Exception $e) {
            \Log::error('Failed to save base documentation', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch('showToast', type: 'error', message: 'Failed to save: ' . $e->getMessage());
        }
    }

    /**
     * Convert Trix HTML to Markdown.
     */
    private function convertHtmlToMarkdown(string $html): string
    {
        // Remove Trix figure wrappers and extract images
        $html = preg_replace('/<figure[^>]*>.*?(<img[^>]*>).*?<\/figure>/is', '$1', $html);

        // Update image paths from storage to base docs images
        $html = preg_replace_callback(
            '/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i',
            function ($matches) {
                $src = $matches[1];

                // If it's a storage path, extract filename and point to base docs
                if (str_contains($src, '/storage/docs/images/')) {
                    $filename = basename($src);
                    return str_replace($src, "/docs/images/{$filename}", $matches[0]);
                }

                return $matches[0];
            },
            $html
        );

        // Convert HTML to Markdown
        $converter = new \League\HTMLToMarkdown\HtmlConverter([
            'strip_tags' => true,
            'remove_nodes' => 'script style',
        ]);

        return $converter->convert($html);
    }

    /**
     * Email current documentation as PDF to authenticated user.
     */
    public function emailPdf(): void
    {
        if (!$this->viewingArticleTitle || !$this->viewingArticleHtml) {
            $this->dispatch('showToast', type: 'error', message: 'No documentation to send');
            return;
        }

        try {
            // Convert images for PDF embedding
            $stats = [
                'converted_to_data_uri' => 0,
                'conversion_failed' => 0,
            ];
            $transformer = new \HeritageApps\Help\Services\PdfImageTransformer();
            $htmlWithEmbeddedImages = $transformer->transform($this->viewingArticleHtml, $stats);

            // Generate PDF using the documentation template
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('heritageapps-help::pdf.documentation', [
                'title' => $this->viewingArticleTitle,
                'htmlContent' => $htmlWithEmbeddedImages,
            ]);

            // Configure dompdf options for better image handling
            $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
            $pdf->getDomPDF()->set_option('isPhpEnabled', false);
            $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
            $pdf->getDomPDF()->set_option('isFontSubsettingEnabled', true);

            $pdfContent = $pdf->output();

            // Send email with PDF attachment
            \Illuminate\Support\Facades\Mail::to(auth()->user()->email)
                ->send(new \HeritageApps\Help\Mail\DocumentationPdf($this->viewingArticleTitle, $pdfContent));

            $this->dispatch('showToast', type: 'success', message: 'Documentation sent to ' . auth()->user()->email);
        } catch (\Exception $e) {
            \Log::error('Failed to email documentation PDF', ['error' => $e->getMessage()]);
            $this->dispatch('showToast', type: 'error', message: 'Failed to send email. Please try again.');
        }
    }


    /**
     * Ensure proper spacing in HTML for Trix editor.
     * Adds a single line break between block elements so Trix displays proper spacing.
     */
    private function ensureTrixSpacing(string $html): string
    {
        // Add a single line break after closing block-level tags for subtle spacing
        $html = preg_replace('/<\/(p|h2|h3|ul|ol|blockquote)>/', "$0<br>", $html);

        // Remove any trailing breaks at the end
        $html = preg_replace('/(<br>\s*)+$/', '', $html);

        return $html;
    }

    /**
     * View side-by-side comparison of core vs custom version.
     */
    public function viewComparison(string $key): void
    {
        // Only editors can view comparisons
        abort_unless($this->canEditArticles(), 403);

        $docs = app(DocumentationService::class);
        $this->comparisonData = $docs->getComparison($key);

        if (!$this->comparisonData) {
            $this->dispatch('showToast', type: 'error', message: 'Unable to load comparison - core or custom version not found');
            return;
        }

        $this->comparisonKey = $key;
        $this->showingComparison = true;
    }

    /**
     * Close comparison view and return to normal view.
     */
    public function closeComparison(): void
    {
        $this->showingComparison = false;
        $this->comparisonKey = null;
        $this->comparisonData = null;
    }

    /**
     * Adopt core version - replace tenant customization with current core version.
     */
    public function adoptCoreVersion(): void
    {
        // Only editors can adopt core version
        abort_unless($this->canEditArticles(), 403);

        if (!$this->comparisonKey || !$this->comparisonData) {
            return;
        }

        $docs = app(DocumentationService::class);
        $coreVersion = $docs->getCoreVersion($this->comparisonKey);

        if (!$coreVersion) {
            $this->dispatch('showToast', type: 'error', message: 'Core version not found');
            return;
        }

        // Find or create the article
        $articleClass = config('help.models.article');
        $article = $articleClass::firstOrNew(['key' => $this->comparisonKey]);

        // Update with core version content
        $article->title = $coreVersion['title'];
        $article->content = $coreVersion['html'];
        $article->doc_type = $coreVersion['doc_type'];
        $article->edited_by = auth()->id();
        $article->save();

        // Publish it
        if (!$article->isPublished()) {
            $article->publish();
        }

        // Create version record
        $article->createVersion('Adopted core version', auth()->id());

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget("docs.rendered.{$this->comparisonKey}");

        // Refresh outdated list
        $this->outdatedCustomizations = $docs->getOutdatedCustomizations();

        // Refresh the article list to show updated status
        $this->allArticles = $docs->getAllMeta($this->showDrafts);
        $this->displayArticles = $this->allArticles;
        $this->sortDisplayArticles();

        // Reload comparison with fresh data to show changes took effect
        $this->comparisonData = $docs->getComparison($this->comparisonKey);

        // Stay on comparison view - don't close it
        // User can see the comparison is now identical after adopting core version

        $this->dispatch('showToast', type: 'success', message: 'Core version adopted successfully');
    }

    public function render()
    {
        return view('heritageapps-help::livewire.help-library-modal');
    }
}
