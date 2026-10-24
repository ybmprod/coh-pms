<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money_format_usd(float $amount): string
{
    return 'US$ ' . number_format($amount, 2, '.', ',');
}

function format_date(?string $value): string
{
    if (empty($value)) {
        return '-';
    }

    $date = date_create_from_format('Y-m-d', $value);

    if ($date === false) {
        return '-';
    }

    return $date->format('d M Y');
}

function format_datetime(?string $value): string
{
    if (empty($value)) {
        return '-';
    }

    $date = date_create($value);

    if ($date === false) {
        return '-';
    }

    return $date->format('d M Y, H:i');
}

/**
 * Shows a booking, payment, account or venue status as a polished coloured badge with semantic icon.
 * WCAG 2.2 AAA compliant, offline compatible using local uicons font.
 */
function status_badge(?string $status, string $fallback = '-'): string
{
    if ($status === null || trim($status) === '') {
        return e($fallback);
    }

    $statusTrim = trim($status);
    $class = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $statusTrim), '-'));

    $icon = match (strtolower($statusTrim)) {
        'pending', 'pending verification', 'pending activation' => 'fi-sr-clock',
        'approved' => 'fi-sr-check',
        'confirmed', 'verified', 'active', 'available' => 'fi-sr-check-circle',
        'completed' => 'fi-sr-trophy',
        'rejected', 'cancelled', 'inactive', 'unavailable', 'invite expired' => 'fi-sr-cross-circle',
        'under maintenance' => 'fi-sr-settings-sliders',
        default => 'fi-sr-info',
    };

    return '<span class="status-badge ' . e($class) . '">'
        . '<i class="fi ' . e($icon) . '" aria-hidden="true"></i>'
        . '<span>' . e($statusTrim) . '</span>'
        . '</span>';
}

/**
 * Returns ' aria-current="page"' when the given route is the page being viewed.
 */
function current_page_attr(string $route): string
{
    $currentRoute = trim((string) ($_GET['r'] ?? ''), '/');

    if ($currentRoute !== '' && !str_contains($currentRoute, '/')) {
        $currentRoute .= '/index';
    }

    return strcasecmp($currentRoute, $route) === 0 ? ' aria-current="page"' : '';
}
