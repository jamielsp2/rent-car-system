<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cr_car_makes')]
class CarMake extends Model
{
    use HasTranslations;

    /** Columns with a version in other languages (see HasTranslations). */
    protected array $translatable = ['name'];

    protected $fillable = ['name', 'logo', 'status', 'order'];

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'make_id');
    }
}
