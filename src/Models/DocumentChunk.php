<?php

namespace HeritageApps\Help\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Documentation chunk with embedding vector for semantic search.
 *
 * For multi-tenant apps: if chunks should be shared across tenants (landlord),
 * create an app model that uses the landlord connection.
 * If chunks should be per-tenant, use the tenant connection.
 */
class DocumentChunk extends Model
{
    protected $fillable = [
        'file_path',
        'section_title',
        'content',
        'embedding',
        'content_length',
        'content_hash',
        'file_modified_at',
    ];

    protected function casts(): array
    {
        return [
            'embedding'        => 'array',
            'file_modified_at' => 'datetime',
        ];
    }

    /**
     * Calculate cosine similarity between this chunk's embedding and a query embedding.
     */
    public function cosineSimilarity(array $queryEmbedding): float
    {
        $chunkEmbedding = $this->embedding;

        if (empty($chunkEmbedding) || empty($queryEmbedding)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;
        $count      = min(count($chunkEmbedding), count($queryEmbedding));

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $chunkEmbedding[$i] * $queryEmbedding[$i];
            $magnitudeA += $chunkEmbedding[$i] * $chunkEmbedding[$i];
            $magnitudeB += $queryEmbedding[$i] * $queryEmbedding[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    /**
     * Scope to find similar chunks based on cosine similarity with a query embedding.
     */
    public function scopeSimilarTo($query, array $queryEmbedding, int $limit = 10)
    {
        $chunks = $query->get();

        return $chunks->map(function ($chunk) use ($queryEmbedding) {
            $chunk->similarity = $chunk->cosineSimilarity($queryEmbedding);
            return $chunk;
        })->sortByDesc('similarity')->take($limit)->values();
    }
}
