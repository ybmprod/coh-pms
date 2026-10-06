<?php
declare(strict_types=1);

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function render_flash(): string
{
    $flash = get_flash();

    if (!$flash) {
        return '';
    }

    $type = e($flash['type']);
    $message = e($flash['message']);

    return '<div class="flash flash-' . $type . '" role="alert">' . $message . '</div>';
}
