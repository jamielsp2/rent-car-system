<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('payments')]
class Payment extends Model
{
    protected $fillable = ['status'];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'payment_channel' => PaymentMethod::class,
            'amount' => 'float',
        ];
    }
}
