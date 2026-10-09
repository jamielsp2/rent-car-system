<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Public URL keys of vehicles (/cars/{key}) and blog posts (/news/{key}).
 */
#[Table('slugs')]
class Slug extends Model
{
    public const CAR = Car::class;

    protected $fillable = ['key', 'reference_id', 'reference_type', 'prefix'];
}
