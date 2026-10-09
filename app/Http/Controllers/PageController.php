<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Post;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * Content pages: about, FAQs and blog.
 */
class PageController extends Controller
{
    public function about(): View
    {
        return view('site.about', [
            'testimonials' => Testimonial::featured(),
        ]);
    }

    public function faqs(): View
    {
        return view('site.faqs', [
            'faqs' => Faq::query()->published()->orderBy('category_id')->orderBy('id')->get(),
        ]);
    }

    public function blog(): View
    {
        return view('site.blog', [
            'posts' => Post::query()->published()->with('slug')->latest()->paginate(9),
        ]);
    }

    public function post(string $key): View
    {
        $post = Post::query()
            ->published()
            ->with('slug')
            ->whereHas('slug', fn (Builder $q) => $q->where('key', $key)->where('prefix', 'news'))
            ->firstOrFail();

        $post->increment('views');

        return view('site.post', [
            'post' => $post,
            'recent' => Post::query()->published()->with('slug')->whereKeyNot($post->getKey())->latest()->limit(3)->get(),
        ]);
    }
}
