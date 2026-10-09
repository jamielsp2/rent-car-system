<?php

namespace App\Models;

use App\Enums\ServicePriceType;
use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Extra a customer can add to a rental (driver, delivery, child seat...).
 */
#[Table('cr_services')]
class Service extends Model
{
    protected $fillable = ['name', 'description', 'price', 'price_type', 'image', 'status'];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'price_type' => ServicePriceType::class,
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    public function totalFor(int $days): float
    {
        return round($this->price_type === ServicePriceType::PerDay ? $this->price * $days : $this->price, 2);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => Media::url($this->image));
    }
}
