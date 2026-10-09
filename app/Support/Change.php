<?php

namespace App\Support;

/**
 * Helpers for dashboard figures: change against a previous period and sparklines.
 */
class Change
{
    /**
     * A percentage change, or percentage points for figures that already are percentages.
     *
     * @return array{text: string, good: bool, flat: bool}|null
     */
    public static function delta(float|int $now, float|int $before, bool $lowerIsBetter = false, bool $absolute = false): ?array
    {
        if ($absolute) {
            $change = round($now - $before, 1);
            $text = ($change > 0 ? '+' : '') . $change . ' pts';
        } else {
            if ((float) $before === 0.0) {
                return $now ? ['text' => 'Nuevo', 'good' => ! $lowerIsBetter, 'flat' => false] : null;
            }

            $change = round(($now - $before) / abs($before) * 100);
            $text = ($change > 0 ? '+' : '') . $change . '%';
        }

        return [
            'text' => $text,
            'flat' => $change == 0,
            'good' => $lowerIsBetter ? $change < 0 : $change > 0,
        ];
    }

    /**
     * SVG paths (line and filled area) for a 100×32 sparkline.
     *
     * @param  array<int|float>  $values
     * @return array{line: string, area: string}|null
     */
    public static function sparkline(array $values): ?array
    {
        if (count($values) < 2 || ! array_filter($values)) {
            return null;
        }

        $min = min($values);
        $range = (max($values) - $min) ?: 1;
        $step = 100 / (count($values) - 1);

        $points = collect($values)->map(fn ($v, $i) => round($i * $step, 2) . ',' . round(30 - ($v - $min) / $range * 28, 2));
        $line = 'M' . $points->implode(' L');

        return ['line' => $line, 'area' => "{$line} L100,32 L0,32 Z"];
    }
}
