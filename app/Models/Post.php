<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Support\Html;
use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Table('posts')]
class Post extends Model
{
    use HasTranslations;

    /** Columns with a version in other languages (see HasTranslations). */
    protected array $translatable = ['name', 'description', 'content'];

    public const TYPE = self::class;

    protected $fillable = ['name', 'description', 'content', 'status', 'image', 'is_featured'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'views' => 'integer'];
    }

    // Category every blog post is filed under.
    public const BLOG_CATEGORY_ID = 11;

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            $post->author_id ??= auth()->id();
            $post->author_type ??= User::class;
        });

        static::created(function (Post $post): void {
            DB::table('post_categories')->insertOrIgnore([
                'post_id' => $post->getKey(),
                'category_id' => self::BLOG_CATEGORY_ID,
            ]);
        });

        static::deleted(function (Post $post): void {
            $post->slug()->delete();
            DB::table('post_categories')->where('post_id', $post->getKey())->delete();
        });
    }

    /**
     * Give the post a unique /news/{key} address.
     */
    public function syncSlug(?string $key = null): void
    {
        $base = Str::slug($key ?: $this->name) ?: 'articulo';
        $key = $base;

        for ($i = 2; Slug::query()->where('prefix', 'news')->where('key', $key)
            ->where(fn ($q) => $q->where('reference_type', '!=', self::TYPE)->orWhere('reference_id', '!=', $this->getKey()))
            ->exists(); $i++) {
            $key = "{$base}-{$i}";
        }

        Slug::query()->updateOrCreate(
            ['reference_type' => self::TYPE, 'reference_id' => $this->getKey()],
            ['key' => $key, 'prefix' => 'news'],
        );

        $this->unsetRelation('slug');
    }

    public function slug(): HasOne
    {
        return $this->hasOne(Slug::class, 'reference_id')->where('reference_type', self::TYPE);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => $this->slug ? lroute('site.post', $this->slug->key) : lroute('site.blog'));
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => Media::url($this->image));
    }

    protected function safeContent(): Attribute
    {
        return Attribute::get(fn () => Html::clean($this->content));
    }
}
