<?php
declare(strict_types=1);

function chart_currency_short(float $value): string
{
    $absolute = abs($value);
    if ($absolute >= 1000000) {
        return 'US$ ' . number_format($value / 1000000, 1) . 'm';
    }
    if ($absolute >= 1000) {
        return 'US$ ' . number_format($value / 1000, $value >= 10000 ? 0 : 1) . 'k';
    }

    return 'US$ ' . number_format($value, 0);
}

function chart_donut_tone(array $segment, int $index): string
{
    $tone = strtolower(trim((string) ($segment['tone'] ?? '')));
    if ($tone !== '') {
        $tone = (string) preg_replace('/[^a-z0-9-]/', '', $tone);
        if ($tone !== '') {
            return $tone;
        }
    }

    return 'palette-' . (($index % 6) + 1);
}

function chart_donut_segments(array $segments, int $limit = 6, string $otherLabel = 'Other'): array
{
    $segments = array_values(array_filter($segments, static function (array $segment): bool {
        return (float) ($segment['value'] ?? 0) > 0;
    }));

    usort($segments, static function (array $left, array $right): int {
        return (float) ($right['value'] ?? 0) <=> (float) ($left['value'] ?? 0);
    });

    if ($limit < 1 || count($segments) <= $limit) {
        return $segments;
    }

    $visible = array_slice($segments, 0, $limit - 1);
    $otherValue = 0.0;
    foreach (array_slice($segments, $limit - 1) as $segment) {
        $otherValue += (float) ($segment['value'] ?? 0);
    }

    if ($otherValue > 0) {
        $visible[] = [
            'label' => $otherLabel,
            'value' => $otherValue,
            'value_label' => null,
            'tone' => 'palette-' . $limit,
        ];
    }

    return $visible;
}

function render_donut_chart(
    array $segments,
    string $ariaLabel = 'Distribution',
    string $caption = 'Total',
    ?string $centerLabel = null
): string
{
    $segments = array_values(array_filter($segments, static function (array $segment): bool {
        return (float) ($segment['value'] ?? 0) > 0;
    }));

    $total = array_sum(array_map(static fn(array $segment): float => (float) $segment['value'], $segments));
    $centerText = $centerLabel ?? number_format($total, 0);
    $centerClass = strlen($centerText) > 10 ? ' donut-total--compact' : '';
    $center = 120.0;
    $radius = 84.0;
    $circumference = 2 * M_PI * $radius;
    $offset = 0.0;

    $svg = '<svg class="donut-svg" viewBox="0 0 240 240" role="img" aria-label="' . e($ariaLabel) . '">';
    $svg .= '<circle class="donut-track" cx="' . $center . '" cy="' . $center . '" r="' . $radius . '"/>';

    foreach ($segments as $index => $segment) {
        $value = (float) $segment['value'];
        $length = ($value / $total) * $circumference;
        $tone = chart_donut_tone($segment, $index);
        $svg .= '<circle class="donut-segment donut-segment--' . e($tone) . '" cx="' . $center . '" cy="' . $center . '" r="' . $radius
            . '" stroke-dasharray="' . round($length, 3) . ' ' . round($circumference, 3)
            . '" stroke-dashoffset="' . round(-$offset, 3) . '">'
            . '<title>' . e((string) $segment['label'] . ': ' . (string) ($segment['value_label'] ?? number_format($value, 0))) . '</title></circle>';
        $offset += $length;
    }

    $svg .= '<text class="donut-total' . $centerClass . '" x="' . $center . '" y="118" text-anchor="middle">'
        . e($centerText) . '</text>';
    $svg .= '<text class="donut-caption" x="' . $center . '" y="142" text-anchor="middle">' . e($caption) . '</text>';
    $svg .= '</svg>';

    return $svg;
}

function render_donut_legend(array $segments, callable $valueFormatter, bool $showShare = false): string
{
    $segments = array_values(array_filter($segments, static function (array $segment): bool {
        return (float) ($segment['value'] ?? 0) > 0;
    }));

    $total = array_sum(array_map(static fn(array $segment): float => (float) $segment['value'], $segments));
    $html = '<ul class="donut-legend' . ($showShare ? ' donut-legend--share' : '') . '">';

    foreach ($segments as $index => $segment) {
        $value = (float) $segment['value'];
        $share = $total > 0 ? ($value / $total) * 100 : 0.0;
        $tone = chart_donut_tone($segment, $index);
        $html .= '<li>'
            . '<span class="legend-dot legend-dot--' . e($tone) . '"></span>'
            . '<span class="donut-legend__label" title="' . e((string) $segment['label']) . '">' . e((string) $segment['label']) . '</span>'
            . '<strong>' . e((string) $valueFormatter($segment))
            . ($showShare ? '<small>' . e(number_format($share, 1)) . '%</small>' : '')
            . '</strong>'
            . '</li>';
    }

    return $html . '</ul>';
}

function render_status_donut(array $segments, string $ariaLabel = 'Booking Status Distribution'): string
{
    return render_donut_chart($segments, $ariaLabel, 'Bookings');
}
