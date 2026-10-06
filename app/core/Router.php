<?php
declare(strict_types=1);

class Router
{
    public function dispatch(string $route): array
    {
        $normalized = trim((string) $route, '/');
        $parts = $normalized === '' ? ['home', 'index'] : preg_split('#/#', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 1) {
            $parts[] = 'index';
        }

        $controller = ucfirst($parts[0]) . 'Controller';
        $action = $parts[1] ?? 'index';

        return [$controller, $action];
    }
}
