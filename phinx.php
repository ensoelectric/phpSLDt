<?php

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'phpSLDt',
            'user' => 'root',
            'pass' => 'toor',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'phpSLDt_dev',
            'user' => 'root',
            'pass' => 'toor',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'phpSLDt_testing',
            'user' => 'root',
            'pass' => 'toor',
            'port' => '3306',
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];
