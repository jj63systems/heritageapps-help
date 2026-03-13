<?php

namespace HeritageApps\Help\Livewire;

use HeritageApps\Help\Services\DocumentationService;
use Livewire\Attributes\On;
use Livewire\Component;

class HelpPanel extends Component
{
    public bool $showPanel = false;

    public ?string $docKey = null;

    public ?string $title = null;

    public ?string $html = null;

    public bool $notFound = false;

    #[On('open-help')]
    public function openHelp(string $key = ''): void
    {
        $this->showPanel = true;
        $this->notFound  = false;

        if (empty($key)) {
            $this->notFound = true;
            $this->docKey   = null;
            $this->title    = null;
            $this->html     = null;

            return;
        }

        $rendered = app(DocumentationService::class)->getRendered($key);

        if (! $rendered) {
            $this->notFound = true;
            $this->docKey   = $key;
            $this->title    = null;
            $this->html     = null;

            return;
        }

        $this->docKey = $key;
        $this->title  = $rendered['title'];
        $this->html   = $rendered['html'];
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
    }

    public function render()
    {
        return view('heritageapps-help::livewire.help-panel');
    }
}
