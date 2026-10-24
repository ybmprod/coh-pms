<?php
declare(strict_types=1);

// CHAPTER 5.3.2 - Venue Management
function save_uploaded_venue_image(array $file): ?string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] <= 0 || $file['size'] > 2 * 1024 * 1024) {
        return null;
    }

    // 1. Strict extension whitelist check on client-supplied filename
    $originalName = trim((string) ($file['name'] ?? ''));
    $clientExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if ($originalName === '' || !in_array($clientExtension, $allowedExtensions, true)) {
        return null;
    }

    // 2. Strict finfo MIME-type inspection on uploaded file bytes
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimeTypes = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    if (!isset($allowedMimeTypes[$mimeType]) || !in_array($clientExtension, $allowedMimeTypes[$mimeType], true)) {
        return null;
    }

    $extension = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };

    if (!is_dir(APP_UPLOAD_PATH) && !mkdir(APP_UPLOAD_PATH, 0755, true) && !is_dir(APP_UPLOAD_PATH)) {
        return null;
    }

    // Ensure upload directory has .htaccess blocking script execution
    $htaccessPath = APP_UPLOAD_PATH . '/.htaccess';
    if (!is_file($htaccessPath)) {
        @file_put_contents(
            $htaccessPath,
            "Options -Indexes -ExecCGI\n\n<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>\n<IfModule mod_php7.c>\n    php_flag engine off\n</IfModule>\n<IfModule mod_php8.c>\n    php_flag engine off\n</IfModule>\n\n<FilesMatch \"(?i)\\.(php[0-9]*|phtml|phar|pl|py|cgi|sh|asp|aspx|jsp)$\">\n    Require all denied\n</FilesMatch>\n"
        );
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = APP_UPLOAD_PATH . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return $filename;
}

function venue_image_url(?string $imagePath): string
{
    if ($imagePath === null || trim($imagePath) === '') {
        return '';
    }

    $filename = basename(str_replace('\\', '/', $imagePath));
    return BASE_URL . '/uploads/venues/' . rawurlencode($filename);
}
