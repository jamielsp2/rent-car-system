<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cr_car_makes')]
class CarMake extends Model
{
    protected $fillable = ['name', 'logo', 'status', 'order'];

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'make_id');
    }
}
