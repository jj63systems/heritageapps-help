<?php

namespace HeritageApps\Help\Livewire;

use HeritageApps\Help\Contracts\AppContextInterface;
use HeritageApps\Help\Services\DocumentationService;
use Livewire\Attributes\Url;
use Livewire\Component;

class HelpCentre extends Component
{
    #[Url]
    public ?string $key = null;

    public ?string $title = null;

    public ?string $html = null;

    public ?string $category = null;

    public bool $notFound = false;

    public array $allArticles = [];

    public string $search = '';

    /**
     * Section keys displayed in the help centre.
     * Apps can override this by publishing and modifying the Livewire component,
     * or by overriding sections in a subclass.
     *
     * @var array<string, string>
     */
    protected array $sections = [
        'guides'          => 'Guides & Workflows',
        'screen_reference'=> 'Screen Reference',
        'faqs'            => 'FAQs',
        'uncategorised'   => 'Other',
    ];

    /**
     * Keys to promote into a "Getting Started" section.
     *
     * @var array<string>
     */
    protected array $gettingStartedKeys = [];

    public function mount(): void
    {
        $this->authorizeAccess();

        $docs = app(DocumentationService::class);

        if ($this->key) {
            $rendered = $docs->getRendered($this->key);

            if (! $rendered) {
                $this->notFound = true;

                return;
            }

            $this->title    = $rendered['title'];
            $this->html     = $rendered['html'];
            $this->category = $rendered['category'] ?? 'app';
        } else {
            $this->allArticles = $docs->getAllMeta();
        }
    }

    #[\Livewire\Attributes\Computed]
    public function filteredArticles()
    {
        if ($this->search !== '') {
            return array_map(fn ($result) => [
                'key'      => $result['key'],
                'title'    => $result['title'],
                'category' => $result['category'],
                'doc_type' => $result['doc_type'] ?? $this->getDocType($result['key']),
                'snippet'  => $result['snippet'] ?? '',
            ], app(DocumentationService::class)->search($this->search, limit: 100));
        }

        return $this->allArticles;
    }

    #[\Livewire\Attributes\Computed]
    public function sectionedArticles()
    {
        $articles = $this->filteredArticles;

        $sections = [
            'getting_started'  => [],
            'guides'           => [],
            'roles'            => [],
            'screen_reference' => [],
            'data_standards'   => [],
            'faqs'             => [],
            'uncategorised'    => [],
        ];

        foreach ($articles as $article) {
            if (! empty($this->gettingStartedKeys) && in_array($article['key'], $this->gettingStartedKeys)) {
                $sections['getting_started'][] = $article;
            } elseif (($article['doc_type'] ?? '') === 'guide') {
                $sections['guides'][] = $article;
            } elseif (($article['doc_type'] ?? '') === 'role') {
                $sections['roles'][] = $article;
            } elseif (($article['doc_type'] ?? '') === 'reference') {
                $sections['screen_reference'][] = $article;
            } elseif (($article['doc_type'] ?? '') === 'standard') {
                $sections['data_standards'][] = $article;
            } elseif (($article['doc_type'] ?? '') === 'faq') {
                $sections['faqs'][] = $article;
            } else {
                $sections['uncategorised'][] = $article;
            }
        }

        foreach ($sections as &$section) {
            usort($section, fn ($a, $b) => strcasecmp($a['title'], $b['title']));
        }

        return $sections;
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    protected function getDocType(string $key): string
    {
        $article = collect($this->allArticles)->firstWhere('key', $key);

        return $article['doc_type'] ?? 'reference';
    }

    protected function authorizeAccess(): void
    {
        if (! auth()->check()) {
            abort(403);
        }

        $context = app()->bound(AppContextInterface::class)
            ? app(AppContextInterface::class)
            : null;

        if ($context && ! $context->canViewHelp(auth()->user())) {
            abort(403);
        }
    }

    public function render()
    {
        return view('heritageapps-help::livewire.help-centre');
    }
}
