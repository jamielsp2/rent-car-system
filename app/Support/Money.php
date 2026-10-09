<?php

namespace App\Support;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Support\Collection;

class Money
{
    /**
     * Amounts are stored in the default (base) currency; used in the admin.
     */
    public static function format(float|int|string|null $amount): string
    {
        return Currency::default()?->format($amount) ?? '$' . number_format((float) $amount, 2);
    }

    /**
     * Public site: converts a base amount into the visitor's chosen currency.
     */
    public static function display(float|int|string|null $amount): string
    {
        $currency = static::current();

        return $currency ? $currency->formatWithCode($currency->convert($amount)) : static::format($amount);
    }

    /**
     * Currency picked by the visitor, or the one configured for the site.
     */
    public static function current(): ?Currency
    {
        $code = session('currency') ?: Setting::get('site_default_currency');

        return ($code ? static::currencies()->firstWhere('title', $code) : null)
            ?? static::currencies()->firstWhere('is_default', true);
    }

    /**
     * Loaded once per request (prices are formatted many times per page).
     *
     * @return Collection<int, Currency>
     */
    public static function currencies(): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has('currencies')) {
            $attributes->set('currencies', Currency::query()->orderBy('order')->get());
        }

        return $attributes->get('currencies');
    }
}
