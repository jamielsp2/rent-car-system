<?php

namespace App\Filament\Support;

use App\Services\Groq;
use App\Services\Translator;
use App\Support\Lang;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

/**
 * "English version" block of an admin form. The fields are saved in the model's
 * `translations` JSON ({"en": {"name": "..."}}) and the header button translates the Spanish
 * fields above with the AI.
 */
class TranslationSection
{
    public const LOCALE = 'en';

    /**
     * @param  array<string, array{label: string, type?: string, rows?: int, max?: int}>  $fields  Spanish field name => settings; type: text, textarea or rich
     */
    public static function make(array $fields, string $heading = 'Versión en inglés (English)', ?\Closure $path = null): Section
    {
        $locale = static::LOCALE;
        $path ??= fn (string $name) => "translations.{$locale}.{$name}";
        $components = [];

        foreach ($fields as $name => $field) {
            $state = $path($name);
            $type = $field['type'] ?? 'text';

            $components[] = match ($type) {
                'rich' => RichEditor::make($state)
                    ->label($field['label'])
                    ->toolbarButtons($field['toolbar'] ?? [['bold', 'italic', 'underline'], ['h2', 'h3'], ['bulletList', 'orderedList'], ['undo', 'redo']])
                    ->columnSpanFull(),
                'textarea' => Textarea::make($state)->label($field['label'])->rows($field['rows'] ?? 2)->maxLength($field['max'] ?? null)->columnSpanFull(),
                default => TextInput::make($state)->label($field['label'])->maxLength($field['max'] ?? 191)->columnSpanFull(),
            };
        }

        return Section::make($heading)
            ->description('Se muestra en la web cuando el visitante elige English. Si un campo queda vacío, se muestra el texto en español.')
            ->icon(Heroicon::OutlinedLanguage)
            ->collapsible()
            ->collapsed()
            ->visible(fn () => Lang::multiple() && in_array($locale, Lang::enabled(), true))
            ->headerActions([static::translateAction(array_keys($fields), $path)])
            ->schema($components);
    }

    /** "Traducir desde español" button: fills the English fields from the Spanish ones. */
    public static function translateAction(array $names, ?\Closure $path = null): Action
    {
        $locale = static::LOCALE;
        $path ??= fn (string $name) => "translations.{$locale}.{$name}";

        return Action::make('translate_' . $locale)
            ->label('Traducir desde español')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('primary')
            ->visible(fn () => Groq::available())
            ->requiresConfirmation()
            ->modalHeading('Traducir con IA')
            ->modalDescription('Reemplaza el texto en inglés por una traducción del español. Revísala antes de guardar.')
            ->action(function (Get $get, Set $set) use ($names, $locale, $path): void {
                $source = [];

                foreach ($names as $name) {
                    $source[$name] = $get($name);
                }

                try {
                    $translated = app(Translator::class)->translate($source, $locale);
                } catch (\Throwable $exception) {
                    Notification::make()->danger()->title('No se pudo traducir')->body($exception->getMessage())->send();

                    return;
                }

                foreach ($translated as $name => $text) {
                    $set($path($name), $text);
                }

                Notification::make()->success()->title('Traducción lista')->body('Revísala y ajústala antes de guardar.')->send();
            });
    }
}
