<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Business-rule exceptions (DomainException) carry messages written for users.
 * Everything else may contain SQL, paths or driver details and must be replaced
 * with a neutral message after being logged by the caller.
 */
function user_error_message(Throwable $exception, string $fallback): string
{
    if ($exception instanceof DomainException) {
        $message = trim($exception->getMessage());
        if ($message !== '') {
            return $message;
        }
    }

    return $fallback;
}

function money_format_usd(float $amount): string
{
    return 'US$ ' . number_format($amount, 2, '.', ',');
}

/**
 * Builds an absolute application URL even when BASE_URL is a relative path.
 */
function app_url(string $route, array $query = []): string
{
    $base = rtrim(BASE_URL, '/');
    if (!preg_match('#^https?://#i', $base)) {
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = $scheme . '://' . $host . '/' . ltrim($base, '/');
    }

    $url = $base . '/?r=' . ltrim($route, '/');
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $url .= '&' . rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
    }

    return $url;
}

function mask_email(string $email): string
{
    $email = trim($email);
    $at = strrpos($email, '@');
    if ($at === false || $at === 0) {
        return $email;
    }

    $local = substr($email, 0, $at);
    $domain = substr($email, $at + 1);
    $visible = strlen($local) <= 2 ? 1 : 2;

    return substr($local, 0, $visible) . str_repeat('*', max(2, strlen($local) - $visible)) . '@' . $domain;
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

function format_time_meridiem(?string $time): string
{
    if (empty($time)) {
        return '-';
    }

    $norm = substr(trim($time), 0, 5);
    if (!preg_match('/^(\d{2}):(\d{2})$/', $norm, $m)) {
        return $time;
    }

    $hour = (int) $m[1];
    $period = $hour >= 12 ? 'PM' : 'AM';
    return $norm . ' ' . $period;
}

function format_booking_time_range(?string $startTime, ?string $endTime): string
{
    if (empty($startTime) || empty($endTime)) {
        return '-';
    }

    $startNorm = substr(trim($startTime), 0, 5);
    $endNorm = substr(trim($endTime), 0, 5);
    $startFormatted = format_time_meridiem($startNorm);
    $endFormatted = format_time_meridiem($endNorm);

    if ($endNorm < $startNorm) {
        return $startFormatted . ' - ' . $endFormatted . ' (Next day)';
    }

    return $startFormatted . ' - ' . $endFormatted;
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
 * Builds a "&key=value" query fragment for report filter links, skipping empty
 * values so CSV exports and print links keep exactly the active filters.
 */
function build_report_query_string(array $filters): string
{
    $pairs = [];
    foreach ($filters as $key => $value) {
        if ($value === '' || $value === 0 || $value === null) {
            continue;
        }
        $pairs[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
    }

    return $pairs === [] ? '' : '&' . implode('&', $pairs);
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
