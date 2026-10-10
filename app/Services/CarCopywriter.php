<?php

namespace App\Services;

use App\Models\CarFuel;
use App\Models\CarMake;
use App\Models\CarTransmission;
use App\Models\CarType;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Writes the web summary and the full description of a vehicle with Groq (OpenAI-compatible API),
 * from the data already filled in the form.
 */
class CarCopywriter
{
    public static function available(): bool
    {
        return filled(config('services.groq.key'));
    }

    /** @return array{summary: string, content: string} */
    public function generate(array $data): array
    {
        if (! static::available()) {
            throw new RuntimeException('Falta la clave de Groq (GROQ_API_KEY en el archivo .env).');
        }

        $facts = $this->facts($data);

        if (blank($facts['Nombre'] ?? null)) {
            throw new RuntimeException('Escribe primero el nombre del vehículo.');
        }

        $lines = collect($facts)->map(fn ($value, $label) => "- {$label}: {$value}")->implode("\n");

        $response = Http::withToken(config('services.groq.key'))
            ->acceptJson()
            ->timeout(40)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.groq.model'),
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres redactor de una empresa de renta de vehículos. Escribes en español neutro, tono cercano y profesional, sin exageraciones. Usa SOLO los datos recibidos: no inventes equipamiento, sistemas, tecnología, seguridad, cifras, consumos ni garantías; si faltan datos, escribe menos en vez de rellenar. Responde únicamente un JSON con las claves "summary" y "content".'],
                    ['role' => 'user', 'content' => "Datos del vehículo:
{$lines}

" . $this->existingSummary($data) . "\"summary\": un párrafo de 1 o 2 frases, máximo 300 caracteres, texto plano, para mostrar bajo el nombre y en Google.
\"content\": descripción completa en HTML simple usando solo <p>, <h3>, <ul>, <li> y <strong> (sin <h1>, <h2> ni estilos). IMPORTANTE: el resumen ya se muestra justo encima y la ficha técnica (año, asientos, puertas, kilometraje, transmisión, combustible, tarifa) ya aparece en la página, así que NO repitas el resumen ni enumeres esos datos. Complementa con algo distinto: para qué tipo de viaje o cliente es ideal (ciudad, familia, carretera, trabajo...), qué se siente al conducirlo o viajar en él según su tipo, y consejos útiles para quien lo va a rentar, sin afirmar nada que no se deduzca de los datos (no describas motor, suspensión, equipamiento ni espacios de almacenamiento concretos; habla de usos y sensaciones generales del tipo de vehículo). Estructura: un párrafo de gancho con otro enfoque que el resumen, un apartado <h3> con 3 o 4 usos o ventajas en lista, y un cierre breve invitando a reservar. Entre 100 y 180 palabras."],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Groq respondió con error ' . $response->status() . ': ' . ($response->json('error.message') ?? 'sin detalle'));
        }

        $json = json_decode((string) $response->json('choices.0.message.content'), true);
        $summary = trim(strip_tags((string) ($json['summary'] ?? '')));
        $content = $this->cleanHtml((string) ($json['content'] ?? ''));

        if ($summary === '' || $content === '') {
            throw new RuntimeException('La IA no devolvió un texto válido. Inténtalo otra vez.');
        }

        return ['summary' => mb_substr($summary, 0, 400), 'content' => $content];
    }

    /** @return array<string, string> */
    protected function facts(array $data): array
    {
        $name = fn (string $model, mixed $id) => filled($id) ? $model::query()->whereKey($id)->value('name') : null;

        return array_filter([
            'Nombre' => $data['name'] ?? null,
            'Marca' => $name(CarMake::class, $data['make_id'] ?? null),
            'Modelo' => $data['model_name'] ?? null,
            'Año' => $data['year'] ?? null,
            'Tipo' => $name(CarType::class, $data['vehicle_type_id'] ?? null),
            'Transmisión' => $name(CarTransmission::class, $data['transmission_id'] ?? null),
            'Combustible' => $name(CarFuel::class, $data['fuel_type_id'] ?? null),
            'Asientos' => $data['number_of_seats'] ?? null,
            'Puertas' => $data['number_of_doors'] ?? null,
            'Kilometraje' => filled($data['mileage'] ?? null) ? number_format((float) $data['mileage'], 0, ',', '.') . ' km' : null,
            'Tarifa por día' => filled($data['rental_rate'] ?? null) ? '$' . $data['rental_rate'] : null,
            'Renta mínima' => filled($data['min_rental_days'] ?? null) ? $data['min_rental_days'] . ' día(s)' : null,
        ], fn ($value) => filled($value));
    }

    protected function existingSummary(array $data): string
    {
        $summary = trim(strip_tags((string) ($data['summary'] ?? '')));

        return $summary === '' ? '' : "Resumen ya escrito (el \"content\" debe decir cosas distintas): {$summary}

";
    }

    protected function cleanHtml(string $html): string
    {
        return trim(strip_tags($html, '<p><h3><ul><ol><li><strong><em><br>'));
    }
}
