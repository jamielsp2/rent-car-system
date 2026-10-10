<?php

namespace App\Services;

use App\Support\Lang;
use RuntimeException;

/**
 * Translates texts of the site from Spanish with Groq, for the "Traducir desde español" buttons.
 */
class Translator
{
    public function __construct(protected Groq $groq)
    {
    }

    /**
     * @param  array<string, ?string>  $fields  name => Spanish text (plain text or simple HTML)
     * @return array<string, string>  the same names with the text in `$locale`
     */
    public function translate(array $fields, string $locale = 'en'): array
    {
        $fields = array_filter($fields, fn ($text) => filled(trim(strip_tags((string) $text))));

        if ($fields === []) {
            throw new RuntimeException('No hay texto en español para traducir.');
        }

        $language = Lang::SUPPORTED[$locale] ?? $locale;
        $json = $this->groq->json([
            ['role' => 'system', 'content' => "You translate texts of a car rental company website from Spanish into {$language}. Write natural, fluent marketing copy a native speaker would write, not a word-by-word translation. Keep the meaning, tone and approximate length. Keep HTML tags exactly as they are and translate only the text inside them. Do not translate brand, make and model names, plate numbers or people's names. Do not add information that is not in the original. Answer ONLY with a JSON object that has exactly the same keys as the input."],
            ['role' => 'user', 'content' => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
        ], 0.3);

        $result = [];

        foreach (array_keys($fields) as $key) {
            $text = $json[$key] ?? null;

            if (is_string($text) && trim($text) !== '') {
                $result[$key] = trim(strip_tags($text, '<p><h2><h3><ul><ol><li><strong><em><u><b><i><br><a><blockquote>'));
            }
        }

        if ($result === []) {
            throw new RuntimeException('La IA no devolvió la traducción. Inténtalo otra vez.');
        }

        return $result;
    }
}
