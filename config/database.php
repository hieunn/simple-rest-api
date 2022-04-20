<?php
return [
    'default' => getenv('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'host' => getenv('DB_HOST', '127.0.0.1'),
            'port' => getenv('DB_PORT', '3306'),
            'dbname' => getenv('DB_DATABASE', ''),
            'username' => getenv('DB_USERNAME', ''),
            'password' => getenv('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ]
    ],
];