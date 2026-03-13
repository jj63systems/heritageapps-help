<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    | Used in the AI assistant's system prompt to describe the application.
    | e.g. "volunteer management system", "museum collection management system"
    */
    'app_name' => env('HELP_APP_NAME', 'management system'),

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | The Eloquent model classes used by this package.
    | Override these in your app's published config to point to your own
    | models (which should extend the base models in this package and add
    | your tenant connection trait).
    */
    'models' => [
        'article'         => \HeritageApps\Help\Models\DocumentationArticle::class,
        'article_version' => \HeritageApps\Help\Models\DocumentationArticleVersion::class,
        'ai_conversation' => \HeritageApps\Help\Models\AiConversation::class,
        'document_chunk'  => \HeritageApps\Help\Models\DocumentChunk::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Path
    |--------------------------------------------------------------------------
    | Path to the markdown documentation files (relative to base_path).
    | The package will look for .md files recursively here.
    */
    'docs_path' => resource_path('docs'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'key'             => env('OPENAI_API_KEY'),
        'model'           => env('OPENAI_MODEL', 'gpt-4o'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'max_tokens'      => (int) env('OPENAI_MAX_TOKENS', 2048),
        'temperature'     => (float) env('OPENAI_TEMPERATURE', 0.7),
        'timeout'         => (int) env('OPENAI_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'ttl_seconds'       => 3600,
        'ttl_local_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG / Embedding Configuration
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'max_chunk_size'      => 2000,
        'min_chunk_size'      => 50,
        'data_query_chunks'   => 8,
        'reference_chunks'    => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Mapping
    |--------------------------------------------------------------------------
    | Maps Laravel route names to documentation keys (dotted notation).
    | Apps publish and customise this in their own config/help.php.
    */
    'route_map' => [],

    /*
    |--------------------------------------------------------------------------
    | Help Centre URL
    |--------------------------------------------------------------------------
    | The base URL path for the help centre and help links.
    | e.g. '/admin/help'
    */
    'help_url_prefix' => env('HELP_URL_PREFIX', '/help'),

];
