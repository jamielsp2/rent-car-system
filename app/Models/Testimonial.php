<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

#[Table('testimonials')]
class Testimonial extends Model
{
    use HasTranslations;

    /** Columns with a version in other languages (see HasTranslations). */
    protected array $translatable = ['content', 'company'];

    protected $fillable = ['name', 'content', 'image', 'company', 'status'];

    /**
     * The testimonials shown on the public site: the latest published ones.
     *
     * @return Collection<int, self>
     */
    public static function featured(int $limit = 6): Collection
    {
        return static::query()->where('status', 'published')->latest('id')->limit($limit)->get();
    }
}
