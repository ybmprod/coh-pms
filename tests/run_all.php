<?php
declare(strict_types=1);

/**
 * Single CI entry point for COH-PMS.
 *
 * Runs a PHP syntax sweep and the six regression suites, then returns a
 * non-zero exit code so OpenCode, GitHub Actions or any other runner can halt
 * deployment when a gate fails.
 */

$root = dirname(__DIR__);
$php = PHP_BINARY !== '' ? PHP_BINARY : 'php';
$failures = [];
$lintChecked = 0;

try {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $path = $file->getPathname();
        if (str_contains(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)) {
            continue;
        }

        $lintChecked++;

        // A BOM or any byte before <?php is sent as output, which makes PHP reject
        // declare(strict_types=1) with "strict_types declaration must be the very
        // first statement in the script". Catch it here with the exact file path.
        $source = file_get_contents($path);
        if ($source === false) {
            $failures[] = 'Strict types: ' . $path . PHP_EOL . 'File could not be read.';
            continue;
        }
        if (str_starts_with($source, "\xEF\xBB\xBF")) {
            $failures[] = 'Strict types: ' . $path . PHP_EOL
                . 'File starts with a UTF-8 BOM before <?php. Save it as UTF-8 without BOM.';
            continue;
        }
        if (!str_starts_with($source, '<?php')) {
            $failures[] = 'Strict types: ' . $path . PHP_EOL
                . 'File does not start with <?php at byte 0 (leading whitespace or bytes are sent as output).';
            continue;
        }
        if (preg_match('/\A<\?php\s+declare\(\s*strict_types\s*=\s*1\s*\)\s*;/i', $source) !== 1) {
            $failures[] = 'Strict types: ' . $path . PHP_EOL
                . 'declare(strict_types=1); is not the first statement after <?php.';
            continue;
        }

        $output = [];
        $exitCode = 0;
        exec(escapeshellarg($php) . ' -l ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            $failures[] = 'Syntax: ' . $path . PHP_EOL . implode(PHP_EOL, $output);
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, '[FAILURE] Syntax sweep could not start: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Syntax sweep: ' . $lintChecked . ' PHP files checked.' . PHP_EOL;

$suites = [
    'logic_test.php' => 'Core business logic',
    'f7_security_test.php' => 'Security hardening',
    'security_hardening_test.php' => 'Security hardening extensions',
    'password_reset_test.php' => 'Customer password reset',
    'pagination_test.php' => 'Database-backed pagination',
    'suite_test.php' => 'Full regression',
];

foreach ($suites as $suite => $label) {
    echo PHP_EOL . '=== ' . $label . ' (' . $suite . ') ===' . PHP_EOL;
    $exitCode = 0;
    passthru(escapeshellarg($php) . ' ' . escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . $suite), $exitCode);
    if ($exitCode !== 0) {
        $failures[] = $suite . ' exited with code ' . $exitCode;
    }
}

if ($failures !== []) {
    echo PHP_EOL . '[FAILURE] COH-PMS verification stopped.' . PHP_EOL;
    foreach ($failures as $failure) {
        echo ' - ' . $failure . PHP_EOL;
    }
    exit(1);
}

echo PHP_EOL . '[SUCCESS] All COH-PMS syntax and regression gates passed.' . PHP_EOL;
exit(0);
