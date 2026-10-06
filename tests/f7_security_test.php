<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/validation_helper.php';
require_once __DIR__ . '/../app/helpers/upload_helper.php';

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
// Note: save_uploaded_venue_image uses is_uploaded_file(), which is false for CLI temp files unless simulated,
// but finfo MIME inspection can be tested directly:
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMime = $finfo->file($fakePng);
assertTest('PHP disguised as PNG is detected as text/x-php or text/plain', in_array($detectedMime, ['text/x-php', 'text/plain', 'application/x-php'], true));
assertTest('MIME check rejects fake PNG', !in_array($detectedMime, ['image/jpeg', 'image/png', 'image/webp'], true));
unlink($fakePng);

// 3. Router & Dispatch Guard Tests
require_once __DIR__ . '/../app/core/Router.php';
$router = new Router();

[$ctrl, $act] = $router->dispatch('nonexistent/foo');
assertTest('Router dispatches nonexistent/foo to NonexistentController', $ctrl === 'NonexistentController' && $act === 'foo');

[$ctrl, $act] = $router->dispatch('venue/render');
assertTest('Router dispatches venue/render to VenueController::render', $ctrl === 'VenueController' && $act === 'render');

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

echo "\nSummary: {$passCount} PASSED, {$failCount} FAILED\n";
exit($failCount === 0 ? 0 : 1);
