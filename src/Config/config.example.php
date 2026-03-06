<?php
declare(strict_types=1);

return [
    'app' => [
        'base_url' => '', // optional; can be auto-detected later
        'env' => 'dev',
    ],
    'db' => [
        'driver' => 'sqlite',
        'sqlite_path' => dirname(__DIR__, 2) . '/var/app.sqlite',
    ],
	'stripe' => [
		'publishable_key' => 'pk_test_REPLACE_ME',
		'secret_key' => 'sk_test_REPLACE_ME',
		'webhook_secret' => 'whsec_REPLACE_ME',
	],
];

