<?php

namespace App\Services;

use App\Models\CarFuel;
use App\Models\CarMake;
use App\Models\CarTransmission;
use App\Models\CarType;
use RuntimeException;

/**
 * Writes the web summary and the full description of a vehicle with Groq, in Spanish and in
 * English, from the data already filled in the form.
 */
class CarCopywriter
{
    public function __construct(protected Groq $groq)
    {
    }

    public static function available(): bool
    {
        return Groq::available();
    }

    /**
     * @return array{summary: string, content: string, seo_title: string, seo_description: string, en: array{summary: string, content: string, seo_title: string, seo_description: string}}
     */
    public function generate(array $data): array
    {
        $facts = $this->facts($data);

        if (blank($facts['Nombre'] ?? null)) {
            throw new RuntimeException('Escribe primero el nombre del vehículo.');
        }

        $lines = collect($facts)->map(fn ($value, $label) => "- {$label}: {$value}")->implode("\n");

        $json = $this->groq->json([
            ['role' => 'system', 'content' => 'You write the web copy of a car rental company, once in Spanish (neutral Spanish) and once in English (US). Friendly, professional tone, no exaggeration, optimized for search engines: use the vehicle name and its type naturally, no keyword stuffing. Use ONLY the data received: do not invent equipment, systems, technology, safety features, figures, fuel consumption or warranties; do not describe engine, suspension, equipment or storage spaces; if data is missing, write less instead of filling in. The English text is NOT a literal translation: write it as a native copywriter would, with the same facts. Answer ONLY with a JSON object: {"es": {"summary": "...", "content": "...", "seo_title": "...", "seo_description": "..."}, "en": {"summary": "...", "content": "...", "seo_title": "...", "seo_description": "..."}}.'],
            ['role' => 'user', 'content' => "Datos del vehículo / Vehicle data:\n{$lines}\n\n" . $this->existing($data) . "\"summary\": 1 or 2 sentences, maximum 300 characters, plain text, shown under the vehicle name and in Google results.\n\"content\": full description in simple HTML using only <p>, <h3>, <ul>, <li> and <strong> (no <h1>, <h2> or styles). IMPORTANT: the summary is already shown right above and the spec sheet (year, seats, doors, mileage, transmission, fuel, rate) already appears on the page, so do NOT repeat the summary nor list those data. Complement it with something different: what kind of trip or customer it is ideal for (city, family, road trips, work...), what the vehicle type feels like to drive or ride in, and useful tips for the renter, without claiming anything that cannot be deduced from the data (speak about general uses and sensations of that type of vehicle). Structure: a hook paragraph with a different angle than the summary, an <h3> section with 3 or 4 uses or advantages as a list, and a short closing inviting to book. Between 100 and 180 words. \"seo_title\": the title for Google, maximum 60 characters, with the vehicle name and a short benefit, no brand repetition. \"seo_description\": the meta description for Google, between 120 and 155 characters, plain text, includes the vehicle name and invites to book, different from the summary. Write both languages."],
        ]);

        $es = $this->clean($json['es'] ?? []);
        $en = $this->clean($json['en'] ?? []);

        if ($es['summary'] === '' || $es['content'] === '' || $en['summary'] === '' || $en['content'] === '') {
            throw new RuntimeException('La IA no devolvió un texto válido. Inténtalo otra vez.');
        }

        return $es + ['en' => $en];
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

    protected function existing(array $data): string
    {
        $summary = trim(strip_tags((string) ($data['summary'] ?? '')));

        return $summary === '' ? '' : "Resumen ya escrito, el \"content\" debe decir cosas distintas: {$summary}\n\n";
    }

    /** @return array{summary: string, content: string, seo_title: string, seo_description: string} */
    protected function clean(mixed $texts): array
    {
        $texts = is_array($texts) ? $texts : [];

        return [
            'summary' => mb_substr(trim(strip_tags((string) ($texts['summary'] ?? ''))), 0, 400),
            'content' => trim(strip_tags((string) ($texts['content'] ?? ''), '<p><h3><ul><ol><li><strong><em><br>')),
            'seo_title' => mb_substr(trim(strip_tags((string) ($texts['seo_title'] ?? ''))), 0, 70),
            'seo_description' => mb_substr(trim(strip_tags((string) ($texts['seo_description'] ?? ''))), 0, 170),
        ];
    }
}
