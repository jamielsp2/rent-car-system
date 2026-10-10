<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('cr_taxes')]
class Tax extends Model
{
    use HasTranslations;

    /** Columns with a version in other languages (see HasTranslations). */
    protected array $translatable = ['name'];

    protected $fillable = ['name', 'percentage', 'amount', 'amount_type', 'status', 'priority'];

    protected function casts(): array
    {
        return [
            'percentage' => 'float',
            'amount' => 'float',
        ];
    }
}
