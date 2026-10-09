<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('cr_bookings')]
class Booking extends Model
{
    protected $fillable = [
        'status',
        'note',
        'completion_miles',
        'completion_gas_level',
        'completion_damage_images',
        'completion_notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'amount' => 'float',
            'sub_total' => 'float',
            'tax_amount' => 'float',
            'coupon_amount' => 'float',
            'credit_amount' => 'float',
            'deposit_amount' => 'float',
            'completion_damage_images' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        // Guest bookings have no customer account; an empty one keeps views simple.
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed()->withDefault();
    }

    /**
     * Each booking rents exactly one vehicle; its dates and price live here.
     */
    public function rental(): HasOne
    {
        return $this->hasOne(BookingCar::class, 'booking_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'cr_booking_service', 'booking_id', 'service_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'reference_id')
            ->where('reference_type', 'like', '%Booking');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(BookingContract::class, 'booking_id');
    }
}
