<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money spent on a vehicle (maintenance, repairs, insurance...); amounts are in
 * the base currency.
 */
#[Table('cr_car_maintenance_histories')]
class CarExpense extends Model
{
    protected $fillable = ['car_id', 'name', 'description', 'amount', 'currency_id', 'date'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CarExpense $expense): void {
            $expense->currency_id ??= Currency::default()?->getKey();
        });
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
