<?php

namespace App\Models\Concerns;

use App\Support\Lang;

/**
 * Title and description written for Google, per language, in the `seo` JSON column.
 */
trait HasSeo
{
    public function initializeHasSeo(): void
    {
        $this->mergeCasts(['seo' => 'array']);
        $this->mergeFillable(['seo']);
    }

    /** The saved SEO `$field` ('title' or 'description') in a language, or null when empty. */
    public function seoText(string $field, ?string $locale = null): ?string
    {
        $text = $this->seo[$locale ?? Lang::current()][$field] ?? null;

        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }
}
