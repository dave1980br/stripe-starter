<?php
declare(strict_types=1);

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

		$configLocal = dirname(__DIR__) . '/Config/config.local.php';
		$configExample = dirname(__DIR__) . '/Config/config.example.php';

		if (file_exists($configLocal)) {
			$config = require $configLocal;
		} else {
			$config = require $configExample;
		}
        $path = $config['db']['sqlite_path'] ?? '';

        if ($path === '' || !file_exists($path)) {
            throw new RuntimeException("SQLite DB not found at: " . $path);
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('PRAGMA foreign_keys = ON;');

        self::$pdo = $pdo;
        return self::$pdo;
    }
}