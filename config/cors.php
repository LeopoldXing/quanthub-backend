<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*'paths' => ['api/*', 'sanctum/csrf-cookie'],*/
    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://quanthub-backend:12345', 'http://quanthub-backend:8000', 'http://quanthub-frontend', 'http://localhost:5173', 'https://localhost:5173', 'https://quanthub.discobroccoli.com', 'http://quanthub.discobroccoli.com', 'https://quanthub.leopoldhsing.cc', 'http://quanthub.leopoldhsing.cc'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
