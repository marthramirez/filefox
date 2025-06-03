<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'fileDownload/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:8080', 'https://lime-boar-288049.hostingersite.com'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => true, 
];
