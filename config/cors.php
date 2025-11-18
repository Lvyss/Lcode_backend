<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie','storage/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173'], // React dev
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
