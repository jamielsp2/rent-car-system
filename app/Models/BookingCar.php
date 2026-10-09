<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('cr_booking_cars')]
class BookingCar extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'rental_start_date' => 'datetime',
            'rental_end_date' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function car(): BelongsTo
    {
        // The booking keeps the car's name and image even if the car was removed.
        return $this->belongsTo(Car::class, 'car_id')->withDefault();
    }

    public function days(): int
    {
        return max(1, (int) ceil($this->rental_start_date->diffInHours($this->rental_end_date) / 24));
    }
}
