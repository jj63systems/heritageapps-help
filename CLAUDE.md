# heritageapps/help — Package Guide

A private Laravel Composer package providing a context-aware help system and AI assistant,
shared across HeritageApps applications (collectorcloud, volunteerapp).

**Stack:** PHP 8.3 | Laravel 12 | Livewire 3 | Tailwind v4 | OpenAI GPT + embeddings

---

## Package Location

```
/Users/jeremyovenden/Herd/heritageapps-help/
```

Namespace: `HeritageApps\Help`
Composer name: `heritageapps/help`

---

## What This Package Provides

| Component | Class | Description |
|---|---|---|
| Help panel | `Livewire\HelpPanel` | Slide-out panel triggered by `open-help` event |
| Search modal | `Livewire\HelpSearchModal` | Full-text + semantic search, triggered by `open-help-search` |
| Help centre | `Livewire\HelpCentre` | Full-page article browser |
| AI assistant | `Livewire\AiHelper` | Draggable chat widget, triggered by `openAIHelper` event |
| Article editor | `Livewire\HelpLibraryModal` | Superadmin WYSIWYG editor, triggered by `open-help-library` event |
| Doc service | `Services\DocumentationService` | Renders markdown + DB articles, search, caching |
| AI service | `Services\AIHelperService` | OpenAI agentic loop, RAG, tool calling |
| Embeddings | `Services\DocumentEmbeddingService` | Generates/searches embeddings via OpenAI |
| Tool registry | `Services\ToolRegistry` | Apps register their own OpenAI function-calling tools |

---

## How Apps Integrate (Checklist)

### 1. Add path repository (local dev)

In the consuming app's `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "../heritageapps-help",
        "options": { "symlink": true }
    }
],
"require": {
    "heritageapps/help": "dev-main"
}
```

Then `composer require heritageapps/help`. The symlink means package changes are
immediately visible without reinstalling.

### 2. Publish config

```bash
php artisan vendor:publish --tag=heritageapps-help-config
```

This creates `config/help.php`. Key values to set:

```php
'app_name'       => 'museum collection management system', // Used in AI system prompt
'help_url_prefix'=> '/app/help',                          // Base URL for help links
'route_map'      => [],                                   // Route name → doc key mapping (see below)
'models'         => [
    'article'         => \App\Models\HelpArticle::class,
    'article_version' => \App\Models\HelpArticleVersion::class,
    'ai_conversation' => \App\Models\AiConversation::class,
    'document_chunk'  => \App\Models\DocumentChunk::class,
],
```

### 3. Publish and run migrations

```bash
# Tenant migrations (documentation_articles, documentation_article_versions,
#                   ai_conversations, tenant_document_chunks)
php artisan vendor:publish --tag=heritageapps-help-migrations-tenant

# Landlord migration (document_chunks — shared embedding index)
php artisan vendor:publish --tag=heritageapps-help-migrations-landlord

# Then run them:
php artisan migrate                        # landlord
php artisan tenants:migrate                # tenant (for all tenant DBs)
```

### 4. Create app models (add tenant connection trait)

The package ships base models with no tenant awareness. Each app extends them:

```php
// app/Models/HelpArticle.php
namespace App\Models;

use App\Models\Concerns\UsesTenantConnection; // your app's trait
use HeritageApps\Help\Models\DocumentationArticle as BaseArticle;

class HelpArticle extends BaseArticle
{
    use UsesTenantConnection;
}
```

Do the same for `HelpArticleVersion`, `AiConversation`, `DocumentChunk`.

Point `config('help.models.*')` at these app models.

### 5. Implement AppContextInterface

This is the main integration contract. Create a class in your app:

```php
// app/Help/AppContext.php
namespace App\Help;

use HeritageApps\Help\Contracts\AppContextInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class AppContext implements AppContextInterface
{
    /**
     * Describe the app's data model to the AI (injected into every request).
     * Keep concise — this goes into every prompt.
     */
    public function getInventory(): string
    {
        return "App: CollectorCloud — museum collection management.\n"
             . "Key models: Catalogue, Item, Location, CcField, Import.\n"
             . "Tenants: " . \App\Models\Tenant::count() . " active institutions.";
    }

    /**
     * Live database stats injected into AI context.
     * Called on every AI request — keep queries fast.
     */
    public function getLiveConfiguration(): string
    {
        return "Items: " . \App\Models\Item::count() . "\n"
             . "Catalogues: " . \App\Models\Catalogue::count();
    }

    /** Can this user use the AI assistant? */
    public function canUseAi(Authenticatable $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /** Can this user view help articles? */
    public function canViewHelp(Authenticatable $user): bool
    {
        return true; // all authenticated users, or restrict as needed
    }

    /** Can this user edit/publish articles in the library editor? */
    public function canEditArticles(Authenticatable $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Which audience values are visible to this user?
     * Article audience field values: 'admin', 'user', 'both'
     */
    public function visibleAudienceFor(Authenticatable $user): array
    {
        return ['admin', 'both']; // adjust per your role model
    }
}
```

### 6. Create a HelpServiceProvider in your app

```php
// app/Providers/HelpServiceProvider.php
namespace App\Providers;

use App\Help\AppContext;
use HeritageApps\Help\Contracts\AppContextInterface;
use HeritageApps\Help\Services\ToolRegistry;
use Illuminate\Support\ServiceProvider;

class HelpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AppContextInterface::class, AppContext::class);
    }

    public function boot(): void
    {
        $registry = app(ToolRegistry::class);

        // Register app-specific AI tools here.
        // Each tool is an OpenAI function-calling function.
        // The AI will call these when it needs live data.
        $registry->register(
            'getCatalogueDetails',
            [
                'name'        => 'getCatalogueDetails',
                'description' => 'Get details about a catalogue including item count and fields.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'Catalogue name'],
                    ],
                    'required'   => ['name'],
                ],
            ],
            fn ($args) => app(\App\Help\Tools\CatalogueTools::class)->getDetails($args)
        );

        // Add more tools as needed...
    }
}
```

Register in `bootstrap/providers.php`:
```php
App\Providers\HelpServiceProvider::class,
```

### 7. Add Livewire components to your layout

In your main app layout blade file (after `@livewireScripts`):

```blade
{{-- Help system --}}
<livewire:heritageapps-help::help-panel />
<livewire:heritageapps-help::help-search-modal />
<livewire:heritageapps-help::help-library-modal />

{{-- AI assistant (admin only — the component checks permissions internally) --}}
@if(auth()->user()?->isAdmin())
    <livewire:heritageapps-help::ai-helper />
@endif
```

The help centre page is a full Livewire page — route it in your app:
```php
Route::get('/help/{key?}', \HeritageApps\Help\Livewire\HelpCentre::class)->name('help.centre');
```

### 8. Add env vars

```env
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
OPENAI_MAX_TOKENS=2048
OPENAI_TEMPERATURE=0.7
OPENAI_TIMEOUT=60
HELP_APP_NAME="museum collection management system"
HELP_URL_PREFIX=/app/help
```

### 9. Index documentation for semantic search

```bash
php artisan docs:index
```

Run this after adding or changing markdown files in `resources/docs/`.
Set up as a scheduled command for production.

---

## Route Map (config/help.php)

Maps Laravel route names to documentation keys so the help panel knows which
article to show for the current screen:

```php
'route_map' => [
    'filament.app.resources.catalogues.index' => 'app.catalogues.index',
    'filament.app.resources.items.index'      => 'app.items.index',
    // etc.
],
```

---

## Documentation Files

Markdown files live in `resources/docs/` of the consuming app (not in the package).
Each file has YAML front matter:

```markdown
---
key: app.catalogues.index
title: Catalogues List
doc_type: reference
audience: admin
related: []
search_keywords: []
---

# Catalogues

Content here...
```

`doc_type` values: `reference`, `guide`, `role`, `standard`, `faq`, `testing`
`audience` values: `admin`, `user`, `both`

---

## How the AI Tools Work

The AI assistant uses OpenAI function calling. When a user asks a data question
("how many items are in the ceramics catalogue?"), the AI calls registered tools
to fetch live data rather than guessing.

Tools are registered in your `HelpServiceProvider::boot()` via `ToolRegistry::register()`.
Each tool needs:
- A unique function name (string)
- An OpenAI function schema (name, description, parameters object)
- A PHP callable that receives `array $args` and returns `array`

The package handles the agentic loop (up to 5 iterations), retries on rate limits,
and ASCII chart normalisation. Your tools just need to return data arrays.

---

## Triggering Help from Anywhere

```js
// Open help panel for a specific article
$dispatch('open-help', { key: 'app.catalogues.index' })

// Open search modal
$dispatch('open-help-search')

// Open AI assistant
$dispatch('openAIHelper')

// Open article editor (superadmin)
$dispatch('open-help-library')
$dispatch('open-help-library-article', { key: 'app.catalogues.index', edit: false })
```

---

## Package Structure

```
src/
  Contracts/AppContextInterface.php   ← implement this in your app
  Services/
    DocumentationService.php          ← markdown + DB rendering, search, caching
    AIHelperService.php               ← OpenAI agentic loop, RAG
    DocumentEmbeddingService.php      ← embedding generation + similarity search
    ToolRegistry.php                  ← register app-specific AI tools here
  Livewire/
    HelpPanel.php                     ← slide-out panel
    HelpSearchModal.php               ← search modal
    HelpCentre.php                    ← full-page help browser
    AiHelper.php                      ← chat widget
    HelpLibraryModal.php              ← superadmin article editor
  Models/
    DocumentationArticle.php          ← extend + add tenant trait
    DocumentationArticleVersion.php   ← extend + add tenant trait
    AiConversation.php                ← extend + add tenant trait
    DocumentChunk.php                 ← extend + add tenant trait (landlord DB)
  Providers/
    HeritageAppsHelpServiceProvider.php
config/help.php
database/migrations/
  tenant/   ← documentation_articles, versions, ai_conversations, document_chunks
  landlord/ ← document_chunks (shared embedding index)
resources/views/livewire/
  help-panel.blade.php
  help-search-modal.blade.php
  help-centre.blade.php
  ai-helper.blade.php
  help-library-modal.blade.php
resources/views/pdf/
  ai-chat.blade.php
```

---

## Known Apps Using This Package

| App | Path | Status |
|---|---|---|
| volunteerapp | `/Users/jeremyovenden/Herd/volunteerapp` | Source (not yet refactored to use package) |
| collectorcloud | `/Users/jeremyovenden/Herd/collectorcloud` | Integration in progress |
