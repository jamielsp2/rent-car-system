<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rental contract: vehicle condition and signatures at pickup (delivery),
 * and the return inspection when the customer brings the vehicle back.
 */
#[Table('cr_booking_contracts')]
class BookingContract extends Model
{
    protected $guarded = ['id', 'booking_id'];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'damage_marks' => 'array',
            'pickup_images' => 'array',
            'signed_at' => 'datetime',
            'pickup_mileage' => 'integer',
            'return_mileage' => 'integer',
            'return_checklist' => 'array',
            'return_damage_marks' => 'array',
            'return_images' => 'array',
            'return_extra_charges' => 'float',
            'returned_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    /**
     * Items handed over at pickup that were not checked back in.
     */
    public function missingItems(): array
    {
        return array_values(array_diff($this->checklist ?? [], $this->return_checklist ?? []));
    }

    public function drivenDistance(): ?int
    {
        if ($this->pickup_mileage === null || $this->return_mileage === null) {
            return null;
        }

        return max(0, $this->return_mileage - $this->pickup_mileage);
    }
}
