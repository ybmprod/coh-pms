<?php
declare(strict_types=1);

/**
 * Server-side pagination helpers shared by the staff list pages and the report
 * tables. The page size can be tuned with PAGINATION_PER_PAGE without code
 * changes, and every page request is clamped to the valid range so a tampered
 * query string can never request an out-of-range offset.
 */

function pagination_per_page(int $default = 15): int
{
    $configured = (int) (env_value('PAGINATION_PER_PAGE', (string) $default) ?? $default);

    return max(5, min(100, $configured > 0 ? $configured : $default));
}

/**
 * @return array{page:int,per_page:int,total:int,pages:int,offset:int,from:int,to:int}
 */
function pagination_meta(int $total, int $perPage, int $page): array
{
    $perPage = max(1, $perPage);
    $total = max(0, $total);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'pages' => $pages,
        'offset' => $offset,
        'from' => $total === 0 ? 0 : $offset + 1,
        'to' => min($total, $offset + $perPage),
    ];
}

function pagination_page_param(?string $raw, int $total, int $perPage): array
{
    $page = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return pagination_meta($total, $perPage, $page === false ? 1 : (int) $page);
}

function pagination_url(string $route, array $query, string $pageParam, int $page, string $anchor = ''): string
{
    unset($query[$pageParam], $query['r']);
    $query[$pageParam] = $page;

    $url = BASE_URL . '/?r=' . ltrim($route, '/');
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $url .= '&' . rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
    }

    return $url . $anchor;
}

/**
 * Renders a compact pager that preserves the current filters. Returns an empty
 * string when the result set fits on one page.
 */
function render_pagination(
    ?array $meta,
    string $route,
    array $query = [],
    string $pageParam = 'page',
    string $anchor = ''
): string {
    if ($meta === null || (int) ($meta['pages'] ?? 1) <= 1) {
        return '';
    }

    $page = (int) $meta['page'];
    $pages = (int) $meta['pages'];

    $numbers = [];
    for ($candidate = 1; $candidate <= $pages; $candidate++) {
        if ($candidate === 1 || $candidate === $pages || abs($candidate - $page) <= 2) {
            $numbers[] = $candidate;
        }
    }

    $html = '<nav class="pagination no-print" aria-label="Pagination">';
    $html .= '<span class="pagination__status">Showing ' . (int) $meta['from'] . '&ndash;' . (int) $meta['to']
        . ' of ' . (int) $meta['total'] . '</span>';

    if ($page > 1) {
        $html .= '<a class="button ghost small-button" rel="prev" href="'
            . e(pagination_url($route, $query, $pageParam, $page - 1, $anchor)) . '">Previous</a>';
    }

    $previous = null;
    foreach ($numbers as $number) {
        if ($previous !== null && $number > $previous + 1) {
            $html .= '<span class="pagination__gap" aria-hidden="true">&hellip;</span>';
        }
        if ($number === $page) {
            $html .= '<span class="button primary small-button is-current" aria-current="page">' . e((string) $number) . '</span>';
        } else {
            $html .= '<a class="button ghost small-button" href="'
                . e(pagination_url($route, $query, $pageParam, $number, $anchor)) . '">' . e((string) $number) . '</a>';
        }
        $previous = $number;
    }

    if ($page < $pages) {
        $html .= '<a class="button ghost small-button" rel="next" href="'
            . e(pagination_url($route, $query, $pageParam, $page + 1, $anchor)) . '">Next</a>';
    }

    return $html . '</nav>';
}
