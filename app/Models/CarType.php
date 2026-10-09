<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cr_car_types')]
class CarType extends CarAttribute
{
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'vehicle_type_id');
    }
}
