<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * Simple named lookups for vehicles (colors, fuels, transmissions).
 * Each concrete class only sets its table.
 */
abstract class CarAttribute extends Model
{
    use HasTranslations;

    /** Columns with a version in other languages (see HasTranslations). */
    protected array $translatable = ['name'];

    protected $fillable = ['name', 'status', 'order'];
}
