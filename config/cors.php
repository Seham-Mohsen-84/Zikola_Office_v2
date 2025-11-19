<?php
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['https://zorders.zikolaa.com','http://localhost:4200'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN', 'X-App-Language','4200'],

    'exposed_headers' => ['X-App-Language'],

    'max_age' => 0,

    'supports_credentials' => true,

];
