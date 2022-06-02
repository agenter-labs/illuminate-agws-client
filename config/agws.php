<?php

return [
    'cache_ttl' => env('AGWS_CACHE_TTL', 3600),
    'cache_store' => env('AGWS_CACHE_STORE', 'array'),
    'client_name' => env('AGWS_CLIENT_NAME', ''),
    'token_name' => env('AGWS_TOKEN_NAME', 'agws-client-token'),
    'private_key_path' => env('AGWS_PRIVATE_KEY_PATH', ''),
    'public_key_path' => env('AGWS_PUBLIC_KEY_PATH', ''),
    // Define services
    'services' => [

    ]
];