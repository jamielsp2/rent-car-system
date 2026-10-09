<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('cr_invoice_items')]
class InvoiceItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'sub_total' => 'float',
            'amount' => 'float',
        ];
    }
}
