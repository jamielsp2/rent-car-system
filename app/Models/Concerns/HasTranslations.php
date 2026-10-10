<?php

namespace App\Models\Concerns;

use App\Support\Lang;

/**
 * Texts in more than one language. The model's own columns are the Spanish original;
 * the `translations` JSON column holds the others ({"en": {"name": "..."}}). Reading a
 * translatable attribute returns the text in the language being shown, and the Spanish
 * one when that translation is empty.
 *
 * The model lists its columns in `$translatable`.
 */
trait HasTranslations
{
    public function initializeHasTranslations(): void
    {
        $this->mergeCasts(['translations' => 'array']);
        $this->mergeFillable(['translations']);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (! Lang::isDefault() && in_array($key, $this->translatable ?? [], true)) {
            $translated = $this->translation($key, Lang::current());

            if (filled($translated)) {
                return $translated;
            }
        }

        return $value;
    }

    /** The saved text of `$key` in `$locale`, or null when there is none (Spanish: the original). */
    public function translation(string $key, string $locale): ?string
    {
        if (Lang::isDefault($locale)) {
            return parent::getAttribute($key);
        }

        $text = parent::getAttribute('translations')[$locale][$key] ?? null;

        return is_string($text) ? $text : null;
    }

    /** Whether every translatable text that has a Spanish original also exists in `$locale`. */
    public function isTranslated(string $locale, ?array $keys = null): bool
    {
        foreach ($keys ?? $this->translatable ?? [] as $key) {
            if (filled(parent::getAttribute($key)) && blank($this->translation($key, $locale))) {
                return false;
            }
        }

        return true;
    }
}
