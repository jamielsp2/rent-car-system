<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cr_invoices')]
class Invoice extends Model
{
    protected $fillable = ['status', 'paid_at', 'payment_id'];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'sub_total' => 'float',
            'tax_amount' => 'float',
            'discount_amount' => 'float',
            'amount' => 'float',
            'paid_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }
}
