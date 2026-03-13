<?php

namespace HeritageApps\Help\Livewire;

use HeritageApps\Help\Services\DocumentationService;
use Livewire\Component;

class HelpSearchModal extends Component
{
    protected $listeners = ['open-help-search' => 'openModal'];

    public bool $showModal = false;

    public string $query = '';

    public array $results = [];

    public function openModal(): void
    {
        $this->showModal = true;
        $this->query     = '';
        $this->results   = [];
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];

            return;
        }

        $this->results = app(DocumentationService::class)->search($this->query, limit: 10);
    }

    public function render()
    {
        return view('heritageapps-help::livewire.help-search-modal');
    }
}
