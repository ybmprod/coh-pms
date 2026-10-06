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

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
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
