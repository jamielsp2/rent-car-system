<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-day price for rentals within a length band (e.g. 7+ days cheaper).
 */
#[Table('cr_car_price_ranges')]
class CarPriceRange extends Model
{
    protected $fillable = ['car_id', 'min_days', 'max_days', 'price_per_day'];

    protected function casts(): array
    {
        return [
            'min_days' => 'integer',
            'max_days' => 'integer',
            'price_per_day' => 'float',
        ];
    }
}
