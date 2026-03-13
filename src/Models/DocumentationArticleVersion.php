<?php

namespace HeritageApps\Help\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentationArticleVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'documentation_article_id',
        'version_number',
        'title',
        'content',
        'change_summary',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(
            config('help.models.article', DocumentationArticle::class)
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Models\User::class),
            'created_by'
        );
    }

    public function isCurrent(): bool
    {
        if (! $this->article) {
            return false;
        }

        return $this->version_number === $this->article->versions()->max('version_number');
    }

    public function previousVersion(): ?self
    {
        return $this->article?->getVersion($this->version_number - 1);
    }

    public function nextVersion(): ?self
    {
        return $this->article?->getVersion($this->version_number + 1);
    }
}
