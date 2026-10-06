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
 * Shows a booking, payment, account or venue status as a coloured badge.
 * The CSS class is the status in lowercase with spaces as hyphens,
 * e.g. "Pending Verification" -> "status-badge pending-verification".
 * An empty status shows the fallback text without a badge.
 */
function status_badge(?string $status, string $fallback = '-'): string
{
    if ($status === null || trim($status) === '') {
        return e($fallback);
    }

    $class = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $status), '-'));

    return '<span class="status-badge ' . e($class) . '">' . e($status) . '</span>';
}

/**
 * Returns ' aria-current="page"' when the given route is the page being viewed,
 * so the sidebar can highlight the current link.
 */
function current_page_attr(string $route): string
{
    $currentRoute = trim((string) ($_GET['r'] ?? ''), '/');

    if ($currentRoute !== '' && !str_contains($currentRoute, '/')) {
        $currentRoute .= '/index';
    }

    return strcasecmp($currentRoute, $route) === 0 ? ' aria-current="page"' : '';
}
