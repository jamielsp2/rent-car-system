<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('cr_taxes')]
class Tax extends Model
{
    protected $fillable = ['name', 'percentage', 'amount', 'amount_type', 'status', 'priority'];

    protected function casts(): array
    {
        return [
            'percentage' => 'float',
            'amount' => 'float',
        ];
    }
}
