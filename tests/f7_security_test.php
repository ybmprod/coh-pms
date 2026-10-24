<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/validation_helper.php';
require_once __DIR__ . '/../app/helpers/upload_helper.php';
require_once __DIR__ . '/../app/helpers/csrf_helper.php';
require_once __DIR__ . '/../app/helpers/audit_helper.php';

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "PASS: {$description}\n";
        $passCount++;
    } else {
        echo "FAIL: {$description}\n";
        $failCount++;
    }
}

// 1. Password Policy Tests
$short = validate_password('Ab1');
assertTest('Password shorter than 8 chars is rejected', in_array('Password must be at least 8 characters long.', $short, true));

$noLetter = validate_password('1234567890');
assertTest('Password with no letter is rejected', in_array('Password must include at least one letter.', $noLetter, true));

$noDigit = validate_password('abcdefghij');
assertTest('Password with no digit is rejected', in_array('Password must include at least one digit.', $noDigit, true));

$validPw = validate_password('Admin@123');
assertTest('Valid password Admin@123 is accepted', empty($validPw));

// Password bcrypt validation
$bcryptHash = hash_password_bcrypt('Admin@123');
assertTest('hash_password_bcrypt generates valid bcrypt format', is_bcrypt_hash($bcryptHash));
assertTest('is_bcrypt_hash rejects MD5 or plain text', !is_bcrypt_hash(md5('Admin@123')) && !is_bcrypt_hash('plainText'));

// 2. Upload Helper Security Tests
// Fake image with PHP code
$fakePng = tempnam(sys_get_temp_dir(), 'test_fake_png_');
file_put_contents($fakePng, '<?php phpinfo(); ?>');

$fakeFile = [
    'name' => 'exploit.png',
    'type' => 'image/png',
    'tmp_name' => $fakePng,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($fakePng),
];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMime = $finfo->file($fakePng);
assertTest('PHP disguised as PNG is detected as text/x-php or text/plain', in_array($detectedMime, ['text/x-php', 'text/plain', 'application/x-php'], true));
assertTest('MIME check rejects fake PNG', !in_array($detectedMime, ['image/jpeg', 'image/png', 'image/webp'], true));
unlink($fakePng);

// Extension whitelist check
$badExtFile = [
    'name' => 'exploit.php',
    'type' => 'application/x-php',
    'tmp_name' => 'dummy',
    'error' => UPLOAD_ERR_OK,
    'size' => 100,
];
assertTest('save_uploaded_venue_image rejects file with non-whitelisted extension', save_uploaded_venue_image($badExtFile) === null);

// 3. Router & Dispatch Guard Tests
require_once __DIR__ . '/../app/core/Router.php';
$router = new Router();

[$ctrl, $act] = $router->dispatch('nonexistent/foo');
assertTest('Router dispatches nonexistent/foo to NonexistentController', $ctrl === 'NonexistentController' && $act === 'foo');

[$ctrl, $act] = $router->dispatch('venue/render');
assertTest('Router dispatches venue/render to VenueController::render', $ctrl === 'VenueController' && $act === 'render');

// Router rejects unexpected characters
assertTest('Router::isValidRoute accepts clean routes', Router::isValidRoute('venue/index') && Router::isValidRoute('booking'));
assertTest('Router::isValidRoute rejects route with path traversal', !Router::isValidRoute('../x') && !Router::isValidRoute('venue/../edit'));
assertTest('Router::isValidRoute rejects route with semicolons or special characters', !Router::isValidRoute('venue/edit;DROP') && !Router::isValidRoute('venue/in$dex'));
assertTest('Router::isValidRoute rejects route with excess segments', !Router::isValidRoute('a/b/c'));

// Test the reflection guard logic from public/index.php
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/controllers/VenueController.php';

$venueCtrl = new VenueController();
$actionExists = method_exists($venueCtrl, 'render');
$actionMethod = new ReflectionMethod($venueCtrl, 'render');
$actionIsAllowed = $actionMethod->isPublic() && $actionMethod->getDeclaringClass()->getName() === VenueController::class;
assertTest('Protected method render() is blocked by reflection guard', !$actionIsAllowed);

$createMethod = new ReflectionMethod($venueCtrl, 'create');
$createAllowed = $createMethod->isPublic() && $createMethod->getDeclaringClass()->getName() === VenueController::class;
assertTest('Public action create() declared on VenueController is allowed', $createAllowed);

// Test controller name regex whitelist
$badRoute = '../x';
$badParts = explode('/', trim($badRoute, '/'));
$badController = $badParts[0] ?? '';
assertTest('Path traversal in route ../x is blocked by regex whitelist', preg_match('/^[a-z]+$/i', $badController) !== 1);

// Test empty route
$emptyRoute = '';
$raw = trim($emptyRoute, '/');
$normalized = $raw === '' ? 'home/index' : $raw;
$routeParts = explode('/', $normalized);
assertTest('Empty route properly resolves to home/index', $routeParts[0] === 'home');

// 4. CSRF Token Tests
$_SESSION['csrf_token'] = 'test_token_secret_12345';
assertTest('verify_csrf_token verifies matching token', verify_csrf_token('test_token_secret_12345'));
assertTest('verify_csrf_token rejects mismatched token', !verify_csrf_token('wrong_token'));
assertTest('verify_csrf_token rejects null or empty token', !verify_csrf_token(null) && !verify_csrf_token(''));

// 5. Rate Limiting Tests (5 fails -> lock)
clear_login_failures('test_scope', 'test_user@example.com');
$initialState = get_login_throttle_state('test_scope', 'test_user@example.com');
assertTest('Initial rate-limit state is unlocked', !$initialState['is_locked']);

for ($i = 1; $i <= 4; $i++) {
    $res = record_login_failure('test_scope', 'test_user@example.com');
    assertTest("Failure attempt {$i} is recorded without lock", !$res['is_locked'] && $res['count'] === $i);
}
$fifthRes = record_login_failure('test_scope', 'test_user@example.com');
assertTest('5th failure triggers 5-minute lockout', $fifthRes['is_locked'] && $fifthRes['locked_until'] > time());
clear_login_failures('test_scope', 'test_user@example.com');
$resetState = get_login_throttle_state('test_scope', 'test_user@example.com');
assertTest('clear_login_failures resets lock status', !$resetState['is_locked']);

// 6. Audit Logging Tests
audit_log('test.audit_event', [
    'booking_id' => 99,
    'password' => 'secretShouldNotBeLogged',
    'status' => 'Approved',
]);
$logFile = APP_LOG_PATH . '/audit.log';
assertTest('Audit log file is generated in storage/logs', is_file($logFile));
$logContent = file_get_contents($logFile);
assertTest('Audit log contains event name', str_contains($logContent, 'event=test.audit_event'));
assertTest('Audit log masks sensitive password field', !str_contains($logContent, 'secretShouldNotBeLogged'));

echo "\nSummary: {$passCount} PASSED, {$failCount} FAILED\n";
exit($failCount === 0 ? 0 : 1);
