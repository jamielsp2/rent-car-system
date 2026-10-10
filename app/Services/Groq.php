<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Groq client (OpenAI-compatible chat API) that always answers with a JSON object.
 */
class Groq
{
    public static function available(): bool
    {
        return filled(config('services.groq.key'));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     */
    public function json(array $messages, float $temperature = 0.7): array
    {
        if (! static::available()) {
            throw new RuntimeException('Falta la clave de Groq (GROQ_API_KEY en el archivo .env).');
        }

        $response = Http::withToken(config('services.groq.key'))
            ->acceptJson()
            ->timeout(60)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.groq.model'),
                'temperature' => $temperature,
                'response_format' => ['type' => 'json_object'],
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Groq respondió con error ' . $response->status() . ': ' . ($response->json('error.message') ?? 'sin detalle'));
        }

        $json = json_decode((string) $response->json('choices.0.message.content'), true);

        if (! is_array($json)) {
            throw new RuntimeException('La IA no devolvió un texto válido. Inténtalo otra vez.');
        }

        return $json;
    }
}
