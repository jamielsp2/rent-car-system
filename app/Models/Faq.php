<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Support\Html;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Table('faqs')]
class Faq extends Model
{
    use HasTranslations;

    /** Columns with a version in other languages (see HasTranslations). */
    protected array $translatable = ['question', 'answer'];

    protected $fillable = ['category_id', 'question', 'answer', 'status'];

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    protected function safeAnswer(): Attribute
    {
        return Attribute::get(fn () => Html::clean($this->answer));
    }
}
