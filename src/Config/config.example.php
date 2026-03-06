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
		'publishable_key' => YOUR_STRIPE_PUBLISHABLE_KEY,
		'secret_key' => YOUR_STRIPE_SECRET_KEY,
		'webhook_secret' => YOUR_STRIPE_WEBHOOK_SECRET,
	],
];

