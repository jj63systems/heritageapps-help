<?php

namespace HeritageApps\Help\Providers;

use HeritageApps\Help\Console\Commands\IndexDocumentation;
use HeritageApps\Help\Contracts\AppContextInterface;
use HeritageApps\Help\Livewire\AiHelper;
use HeritageApps\Help\Livewire\HelpCentre;
use HeritageApps\Help\Livewire\HelpLibraryModal;
use HeritageApps\Help\Livewire\HelpPanel;
use HeritageApps\Help\Livewire\HelpSearchModal;
use HeritageApps\Help\Services\AIHelperService;
use HeritageApps\Help\Services\DocumentEmbeddingService;
use HeritageApps\Help\Services\DocumentationService;
use HeritageApps\Help\Services\ToolRegistry;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class HeritageAppsHelpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/help.php',
            'help'
        );

        // Register the ToolRegistry as a singleton — apps bind tools in their service providers
        $this->app->singleton(ToolRegistry::class);

        // Register services
        $this->app->singleton(DocumentationService::class);
        $this->app->singleton(DocumentEmbeddingService::class);

        // AIHelperService resolves ToolRegistry and optional AppContextInterface
        $this->app->singleton(AIHelperService::class, function ($app) {
            return new AIHelperService(
                embeddingService: $app->make(DocumentEmbeddingService::class),
                toolRegistry: $app->make(ToolRegistry::class),
                appContext: $app->bound(AppContextInterface::class)
                    ? $app->make(AppContextInterface::class)
                    : null,
            );
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/help.php' => config_path('help.php'),
        ], 'heritageapps-help-config');

        // Publish migrations (tenant)
        $this->publishes([
            __DIR__ . '/../../database/migrations/tenant' => database_path('migrations/tenant'),
        ], 'heritageapps-help-migrations-tenant');

        // Publish migrations (landlord — for shared document_chunks table)
        $this->publishes([
            __DIR__ . '/../../database/migrations/landlord' => database_path('migrations/landlord'),
        ], 'heritageapps-help-migrations-landlord');

        // Publish views (apps can override)
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/heritageapps-help'),
        ], 'heritageapps-help-views');

        // Load views with package namespace
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'heritageapps-help');

        // Register Livewire components
        Livewire::component('ha-help-panel', HelpPanel::class);
        Livewire::component('ha-help-search-modal', HelpSearchModal::class);
        Livewire::component('ha-help-centre', HelpCentre::class);
        Livewire::component('ha-help-library-modal', HelpLibraryModal::class);
        Livewire::component('ha-ai-helper', AiHelper::class);

        // Register artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                IndexDocumentation::class,
            ]);
        }
    }
}
