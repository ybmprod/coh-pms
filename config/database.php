<?php
declare(strict_types=1);

/**
 * Resolve a database setting from the real environment or the optional .env file.
 * config.php loads .env first; this fallback keeps direct includes working too.
 */
function db_env(string $key, string $default = ''): string
{
    if (function_exists('env_value')) {
        return env_value($key, $default) ?? $default;
    }

    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? false;
    }

    return ($value === false || $value === '') ? $default : (string) $value;
}

function db_apply_session_identity(PDO $pdo, int $customerId, int $actorId, string $actorRole): void
{
    // Expose the authenticated identity to DEFINER-independent, row-filtered
    // views (database/migrations/2026_11_02_add_row_level_security_views.sql).
    $pdo->exec(
        'SET @coh_pms_customer_id = ' . $customerId
        . ', @coh_pms_actor_id = ' . $actorId
        . ', @coh_pms_actor_role = ' . $pdo->quote($actorRole)
    );
}

/**
 * One application connection and one customer-read connection per request.
 * Models and services therefore share the same transaction handle, and pages
 * that call many models no longer pay for a new handshake on every call.
 * The session identity is refreshed if login/logout changes it mid-request.
 */
function get_db_connection(?string $credentialScope = null): PDO
{
    static $connections = [];

    $scopeKey = $credentialScope === 'customer_read' ? 'customer_read' : 'app';
    $customerId = (int) ($_SESSION['customer_user_id'] ?? 0);
    $actorId = (int) ($_SESSION['user_id'] ?? 0);
    $actorRole = (string) ($_SESSION['auth_role'] ?? '');
    $identityKey = $customerId . '|' . $actorId . '|' . hash('sha256', $actorRole);

    if (isset($connections[$scopeKey])) {
        if (($connections[$scopeKey]['identity'] ?? '') !== $identityKey) {
            db_apply_session_identity($connections[$scopeKey]['pdo'], $customerId, $actorId, $actorRole);
            $connections[$scopeKey]['identity'] = $identityKey;
        }

        return $connections[$scopeKey]['pdo'];
    }

    $host = db_env('DB_HOST', '127.0.0.1');
    $port = db_env('DB_PORT', '3306');
    $dbName = db_env('DB_NAME', 'coh_pms');
    $dbUser = db_env('DB_USER', 'root');
    $dbPass = db_env('DB_PASS', '');
    $charset = db_env('DB_CHARSET', 'utf8mb4');

    // Customer read models use the optional least-privilege account that can
    // only SELECT the row-filtered views. All writes and authentication keep
    // the application account.
    if ($scopeKey === 'customer_read') {
        $customerDbUser = db_env('DB_USER_CUSTOMER', '');
        if ($customerDbUser !== '') {
            $dbUser = $customerDbUser;
            $dbPass = db_env('DB_PASS_CUSTOMER', '');
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $host,
        $port,
        $dbName,
        $charset
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_PERSISTENT => false,
    ];
    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
    }

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    db_apply_session_identity($pdo, $customerId, $actorId, $actorRole);

    $connections[$scopeKey] = [
        'pdo' => $pdo,
        'identity' => $identityKey,
    ];

    return $pdo;
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
        error_log('Database connection failed: ' . $e->getMessage());

        return [
            'ok' => false,
            'message' => 'Database connection failed. Check your .env settings and make sure MySQL is running.',
        ];
    }
}
