<?php

return [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),
    'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
    'api_version' => env('GEMINI_API_VERSION', 'v1'),
    'max_tokens' => 8192,
    'temperature' => 0.7,
    'top_p' => 0.95,
    'top_k' => 40,
    'embedding_dimension' => 3072,
    'chunk_size' => 500,
    'chunk_overlap' => 50,
    'max_chunks_retrieve' => 5,
    'similarity_threshold' => 0.3,
];
