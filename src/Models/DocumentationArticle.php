<?php

namespace HeritageApps\Help\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Base model for tenant-customised documentation articles.
 *
 * Extend this in your app and add your tenant connection trait:
 *
 *   class DocumentationArticle extends \HeritageApps\Help\Models\DocumentationArticle
 *   {
 *       use UsesTenantConnection;
 *   }
 *
 * Then update config('help.models.article') to point to your model.
 */
class DocumentationArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'title',
        'content',
        'doc_type',
        'audience',
        'front_matter',
        'edited_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'front_matter' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'edited_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(
            config('help.models.article_version', DocumentationArticleVersion::class)
        )->orderBy('version_number', 'desc');
    }

    public function currentVersion(): ?DocumentationArticleVersion
    {
        return $this->versions()->first();
    }

    public function getVersion(int $versionNumber): ?DocumentationArticleVersion
    {
        return $this->versions()->where('version_number', $versionNumber)->first();
    }

    public function createVersion(?string $changeSummary = null, ?int $userId = null): DocumentationArticleVersion
    {
        $nextVersion = $this->versions()->max('version_number') + 1;

        return $this->versions()->create([
            'version_number' => $nextVersion,
            'title'          => $this->title,
            'content'        => $this->content,
            'change_summary' => $changeSummary,
            'created_by'     => $userId ?? auth()->id(),
        ]);
    }

    public function restoreVersion(int $versionNumber, ?string $reason = null): bool
    {
        $version = $this->getVersion($versionNumber);

        if (! $version) {
            return false;
        }

        $this->update([
            'title'     => $version->title,
            'content'   => $version->content,
            'edited_by' => auth()->id(),
        ]);

        $this->createVersion("Restored from version {$versionNumber}" . ($reason ? ": {$reason}" : ''));

        return true;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function publish(): void
    {
        if (! $this->isPublished()) {
            $this->update(['published_at' => now()]);
            $this->createVersion('Article published');
        }
    }

    public function unpublish(): void
    {
        if ($this->isPublished()) {
            $this->update(['published_at' => null]);
            $this->createVersion('Article unpublished');
        }
    }
}
