<?php

namespace App\Filament\Support;

use App\Services\CarCopywriter;
use App\Support\Lang;
use App\Support\Seo;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

/**
 * "Google" block of the vehicle and blog forms: title and description of the search result
 * in each language, saved in the model's `seo` JSON ({"es": {"title": ..., "description": ...}}).
 */
class SeoSection
{
    public static function make(bool $withAi = false): Section
    {
        $components = [];

        foreach (Lang::enabled() as $locale) {
            $suffix = Lang::isDefault($locale) ? '' : ' (' . Lang::label($locale) . ')';

            $components[] = TextInput::make("seo.{$locale}.title")
                ->label('Título en Google' . $suffix)
                ->maxLength(70)
                ->live(debounce: 400)
                ->hint(fn (?string $state) => mb_strlen((string) $state) . ' / ' . Seo::TITLE_LIMIT)
                ->hintColor(fn (?string $state) => mb_strlen((string) $state) > Seo::TITLE_LIMIT ? 'warning' : 'gray')
                ->helperText('Hasta 60 caracteres. Vacío = el nombre del vehículo o el título.');
            $components[] = Textarea::make("seo.{$locale}.description")
                ->label('Descripción en Google' . $suffix)
                ->rows(2)
                ->maxLength(170)
                ->live(debounce: 400)
                ->hint(fn (?string $state) => mb_strlen((string) $state) . ' / ' . Seo::DESCRIPTION_LIMIT)
                ->hintColor(fn (?string $state) => mb_strlen((string) $state) > Seo::DESCRIPTION_LIMIT ? 'warning' : 'gray')
                ->helperText('Entre 120 y 155 caracteres. Vacío = el resumen.');
        }

        return Section::make('Cómo aparece en Google')
            ->description('Título y descripción del resultado de búsqueda, por idioma. Si los dejas vacíos se arman solos a partir del nombre y el resumen.')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->collapsible()
            ->collapsed()
            ->headerActions($withAi ? [static::generateAction()] : [])
            ->schema($components);
    }

    /** "Generar con IA": SEO title and description of the vehicle in every enabled language. */
    protected static function generateAction(): Action
    {
        return Action::make('generate_seo_ai')
            ->label('Generar con IA')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('primary')
            ->visible(fn () => CarCopywriter::available())
            ->action(function (Get $get, Set $set): void {
                try {
                    $text = app(CarCopywriter::class)->generate([
                        'name' => $get('name'), 'make_id' => $get('make_id'), 'model_name' => $get('model_name'),
                        'year' => $get('year'), 'vehicle_type_id' => $get('vehicle_type_id'),
                        'transmission_id' => $get('transmission_id'), 'fuel_type_id' => $get('fuel_type_id'),
                        'number_of_seats' => $get('number_of_seats'), 'number_of_doors' => $get('number_of_doors'),
                        'summary' => $get('description'), 'rental_rate' => $get('rental_rate'), 'min_rental_days' => $get('min_rental_days'),
                    ]);
                } catch (\Throwable $exception) {
                    Notification::make()->danger()->title('No se pudo generar el texto')->body($exception->getMessage())->send();

                    return;
                }

                $set('seo.es.title', $text['seo_title']);
                $set('seo.es.description', $text['seo_description']);

                if (Lang::multiple()) {
                    $set('seo.en.title', $text['en']['seo_title']);
                    $set('seo.en.description', $text['en']['seo_description']);
                }

                Notification::make()->success()->title('Texto para Google generado')->body('Revísalo antes de guardar.')->send();
            });
    }
}
