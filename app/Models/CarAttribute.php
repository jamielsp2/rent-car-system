<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple named lookups for vehicles (colors, fuels, transmissions).
 * Each concrete class only sets its table.
 */
abstract class CarAttribute extends Model
{
    protected $fillable = ['name', 'status', 'order'];
}
