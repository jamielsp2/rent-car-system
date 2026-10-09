<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The default currency is the base: every price, booking and payment is
 * stored in it. Other currencies only convert amounts for display, using
 * exchange_rate = units of this currency per 1 unit of the base.
 */
#[Table('cr_currencies')]
class Currency extends Model
{
    protected $fillable = [
        'title',
        'symbol',
        'is_prefix_symbol',
        'decimals',
        'order',
        'exchange_rate',
        'space_between_price_and_currency',
    ];

    protected function casts(): array
    {
        return [
            'is_prefix_symbol' => 'boolean',
            'is_default' => 'boolean',
            'space_between_price_and_currency' => 'boolean',
            'decimals' => 'integer',
            'exchange_rate' => 'float',
        ];
    }

    public static function default(): ?self
    {
        return once(fn () => static::query()->where('is_default', true)->first());
    }

    /**
     * Converts an amount stored in the base currency into this one.
     */
    public function convert(float|int|string|null $baseAmount): float
    {
        return $this->is_default ? (float) $baseAmount : (float) $baseAmount * $this->exchange_rate;
    }

    public function format(float|int|string|null $amount): string
    {
        $number = number_format((float) $amount, $this->decimals ?? 2);
        $space = $this->space_between_price_and_currency ? ' ' : '';

        return $this->is_prefix_symbol ? $this->symbol . $space . $number : $number . $space . $this->symbol;
    }

    /**
     * Format with the currency code when the symbol alone is ambiguous
     * (USD and DOP both use "$").
     */
    public function formatWithCode(float|int|string|null $amount): string
    {
        $shared = Money::currencies()
            ->where('symbol', $this->symbol)
            ->where('id', '!=', $this->getKey())
            ->isNotEmpty();

        return $this->format($amount) . ($shared && ! $this->is_default ? ' ' . $this->title : '');
    }
}
