<?php

namespace HeritageApps\Help\Console\Commands;

use HeritageApps\Help\Services\DocumentEmbeddingService;
use Illuminate\Console\Command;

class IndexDocumentation extends Command
{
    protected $signature = 'help:index-docs
                            {--file= : Index a specific file}
                            {--clear : Clear all existing embeddings first}
                            {--sync : Only index changed files}';

    protected $description = 'Index documentation for semantic search (HeritageApps Help)';

    public function handle(DocumentEmbeddingService $embeddingService): int
    {
        $chunkClass = config('help.models.document_chunk');

        if ($this->option('clear')) {
            $this->info('Clearing existing embeddings...');
            $chunkClass::truncate();
        }

        if ($file = $this->option('file')) {
            return $this->indexFile($file, $embeddingService);
        }

        $files = $embeddingService->getAllDocumentationFiles();
        $this->info('Found ' . count($files) . ' documentation files');

        $totalChunks = 0;
        $skipped     = 0;
        $bar         = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $filePath) {
            $chunksIndexed = $embeddingService->indexFile($filePath);

            if ($chunksIndexed === 0 && $this->option('sync')) {
                $skipped++;
            } else {
                $totalChunks += $chunksIndexed;
                $this->line('');
                $this->info("Indexed: {$filePath} ({$chunksIndexed} chunks)");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Indexing complete!');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Files processed', count($files)],
                ['Files skipped (unchanged)', $skipped],
                ['Total chunks indexed', $totalChunks],
                ['Total chunks in database', $chunkClass::count()],
            ]
        );

        return Command::SUCCESS;
    }

    private function indexFile(string $filePath, DocumentEmbeddingService $embeddingService): int
    {
        $this->info("Indexing file: {$filePath}");

        $chunksIndexed = $embeddingService->indexFile($filePath);

        if ($chunksIndexed === 0) {
            $this->warn('No chunks indexed (file unchanged or not found)');

            return Command::FAILURE;
        }

        $this->info("Indexed {$chunksIndexed} chunks from {$filePath}");

        return Command::SUCCESS;
    }
}
