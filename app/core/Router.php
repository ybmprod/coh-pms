<?php
declare(strict_types=1);

class Router
{
    public static function isValidRoute(string $route): bool
    {
        $normalized = trim($route, '/');
        if ($normalized === '') {
            return true;
        }

        return preg_match('/^[a-zA-Z]+(\/[a-zA-Z]+)?$/', $normalized) === 1;
    }

    public function dispatch(string $route): array
    {
        $normalized = trim((string) $route, '/');
        if (!self::isValidRoute($normalized)) {
            return ['InvalidController', 'invalid'];
        }

        $parts = $normalized === '' ? ['home', 'index'] : preg_split('#/#', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 1) {
            $parts[] = 'index';
        }

        $controller = ucfirst($parts[0]) . 'Controller';
        $action = $parts[1] ?? 'index';

        return [$controller, $action];
    }
}
