<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('cr_customers')]
#[Hidden(['password', 'remember_token', 'email_verify_token'])]
class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'whatsapp',
        'address',
        'dob',
        'status',
        'block_reason',
        'customer_type',
        'license_number',
        'license_region',
        'license_expiry_date',
        'license_status',
        'license_rejected_reason',
        'verification_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'license_status' => LicenseStatus::class,
            'dob' => 'date',
            'confirmed_at' => 'datetime',
            'verified_at' => 'datetime',
            'license_expiry_date' => 'date',
            'license_submitted_at' => 'datetime',
            'license_reviewed_at' => 'datetime',
            'is_vendor' => 'boolean',
            'is_verified' => 'boolean',
            'balance' => 'decimal:2',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }
}
