<?php

namespace HeritageApps\Help\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentEmbeddingService
{
    /**
     * Generate an embedding vector for text using the OpenAI Embeddings API.
     *
     * @return array<float>|null
     */
    public function generateEmbedding(string $text): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('help.openai.key'),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => config('help.openai.embedding_model', 'text-embedding-3-small'),
                    'input' => $text,
                ]);

            if (! $response->successful()) {
                Log::error('HeritageApps Help: Embedding API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            return $response->json('data.0.embedding');
        } catch (\Exception $e) {
            Log::error('HeritageApps Help: Embedding exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Split document content into chunks by markdown headers.
     *
     * @return array<int, array{file_path: string, section_title: string|null, content: string}>
     */
    public function chunkDocument(string $content, string $filePath): array
    {
        $maxChunkSize = config('help.rag.max_chunk_size', 2000);
        $minChunkSize = config('help.rag.min_chunk_size', 50);

        $chunks       = [];
        $lines        = explode("\n", $content);
        $currentChunk = [];
        $currentTitle = null;

        foreach ($lines as $line) {
            if (preg_match('/^(#{1,6})\s+(.+)/', $line, $matches)) {
                // Save previous chunk
                if (! empty($currentChunk)) {
                    $chunkText = implode("\n", $currentChunk);
                    if (strlen(trim($chunkText)) > $minChunkSize) {
                        $chunks[] = [
                            'file_path'     => $filePath,
                            'section_title' => $currentTitle,
                            'content'       => trim($chunkText),
                        ];
                    }
                }

                $currentTitle = trim($matches[2]);
                $currentChunk = [$line];
            } else {
                $currentChunk[] = $line;

                $chunkText = implode("\n", $currentChunk);
                if (strlen($chunkText) > $maxChunkSize) {
                    $chunks[] = [
                        'file_path'     => $filePath,
                        'section_title' => $currentTitle,
                        'content'       => trim($chunkText),
                    ];
                    $currentChunk = [];
                }
            }
        }

        if (! empty($currentChunk)) {
            $chunkText = implode("\n", $currentChunk);
            if (strlen(trim($chunkText)) > $minChunkSize) {
                $chunks[] = [
                    'file_path'     => $filePath,
                    'section_title' => $currentTitle,
                    'content'       => trim($chunkText),
                ];
            }
        }

        return $chunks;
    }

    /**
     * Index a single documentation file, creating embeddings for each chunk.
     * Skips unchanged files (checks content hash).
     */
    public function indexFile(string $filePath): int
    {
        if (! File::exists($filePath)) {
            Log::warning('HeritageApps Help: File not found for indexing', ['file' => $filePath]);

            return 0;
        }

        $content      = File::get($filePath);
        $contentHash  = md5($content);

        $chunkClass      = config('help.models.document_chunk');
        $existingChunk   = $chunkClass::where('file_path', $filePath)->first();

        if ($existingChunk && $existingChunk->content_hash === $contentHash) {
            return 0; // File unchanged
        }

        $chunkClass::where('file_path', $filePath)->delete();

        $chunks  = $this->chunkDocument($content, $filePath);
        $indexed = 0;

        foreach ($chunks as $chunkData) {
            $embedding = $this->generateEmbedding($chunkData['content']);

            if ($embedding) {
                $chunkClass::create([
                    'file_path'       => $chunkData['file_path'],
                    'section_title'   => $chunkData['section_title'],
                    'content'         => $chunkData['content'],
                    'embedding'       => $embedding,
                    'content_length'  => strlen($chunkData['content']),
                    'content_hash'    => $contentHash,
                    'file_modified_at'=> File::lastModified($filePath),
                ]);

                $indexed++;
                usleep(100000); // 0.1s delay to avoid rate-limiting
            }
        }

        return $indexed;
    }

    /**
     * Get all documentation file paths from the configured docs directory.
     *
     * @return array<string>
     */
    public function getAllDocumentationFiles(): array
    {
        $files        = [];
        $docsPath     = config('help.docs_path', resource_path('docs'));

        if (File::exists($docsPath)) {
            foreach (File::allFiles($docsPath) as $file) {
                if ($file->getExtension() === 'md') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Find the most relevant document chunks for a query using cosine similarity.
     *
     * @return array<int, array{file_path: string, section_title: string|null, content: string, similarity: float}>
     */
    public function findRelevantChunks(string $query, int $limit = 10): array
    {
        $queryEmbedding = $this->generateEmbedding($query);

        if (! $queryEmbedding) {
            Log::warning('HeritageApps Help: Failed to generate query embedding');

            return [];
        }

        $chunkClass = config('help.models.document_chunk');
        $chunks     = $chunkClass::query()->similarTo($queryEmbedding, $limit);

        return $chunks->map(fn ($chunk) => [
            'file_path'     => $chunk->file_path,
            'section_title' => $chunk->section_title,
            'content'       => $chunk->content,
            'similarity'    => $chunk->similarity,
        ])->values()->toArray();
    }
}
