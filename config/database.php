<?php
declare(strict_types=1);

function get_db_connection(): PDO
{
    $host = '127.0.0.1';
    $dbName = 'coh_pms';
    $dbUser = 'root';
    $dbPass = '';
    $charset = 'utf8mb4';

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $host,
        $dbName,
        $charset
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO($dsn, $dbUser, $dbPass, $options);
}

function db_test_connection(): array
{
    try {
        $pdo = get_db_connection();
        $pdo->query('SELECT 1');

        return [
            'ok' => true,
            'message' => 'Database connection successful.',
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'message' => 'Database connection failed: ' . $e->getMessage(),
        ];
    }
}
