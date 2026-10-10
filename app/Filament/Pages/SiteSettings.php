<?php

namespace App\Filament\Pages;

use App\Filament\Support\TranslationSection;
use App\Models\Currency;
use App\Models\Setting;
use App\Support\Lang;
use App\Support\SeoFiles;
use App\Support\SiteContent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Everything that makes the public website the business's own: name, contact
 * data, logo, colour, home banner, "about us" texts and footer.
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Web pública';

    protected static ?string $title = 'Web pública';

    protected static ?int $navigationSort = 1;

    protected const KEYS = [
        'site_name', 'site_logo', 'site_favicon', 'site_brand_color',
        'site_phone', 'site_whatsapp', 'site_email', 'site_address', 'site_hours',
        'site_hero_image', 'site_hero_badge', 'site_hero_title', 'site_hero_subtitle',
        'site_about_title', 'site_about_text',
        'site_about_stat_1_number', 'site_about_stat_1_label', 'site_about_stat_1_image',
        'site_about_stat_2_number', 'site_about_stat_2_label', 'site_about_stat_2_image',
        'site_about_stat_3_number', 'site_about_stat_3_label',
        'site_facebook', 'site_instagram', 'site_copyright',
        'site_default_currency',
        // English version of the texts above (see TRANSLATABLE).
        'site_hero_badge_en', 'site_hero_title_en', 'site_hero_subtitle_en', 'site_hours_en',
        'site_about_title_en', 'site_about_text_en',
        'site_about_stat_1_label_en', 'site_about_stat_2_label_en', 'site_about_stat_3_label_en',
        'site_copyright_en',
    ];

    /** Texts that exist in English too: Spanish setting => form label (the English one is saved as "{key}_en"). */
    protected const TRANSLATABLE = [
        'site_hero_badge' => ['label' => 'Etiqueta de la portada (English)'],
        'site_hero_title' => ['label' => 'Título de la portada (English)'],
        'site_hero_subtitle' => ['label' => 'Subtítulo de la portada (English)', 'type' => 'textarea', 'rows' => 3, 'max' => 300],
        'site_hours' => ['label' => 'Horario (English)', 'max' => 120],
        'site_about_title' => ['label' => 'Sobre nosotros: título (English)', 'max' => 150],
        'site_about_text' => ['label' => 'Sobre nosotros: texto (English)', 'type' => 'textarea', 'rows' => 4, 'max' => 1500],
        'site_about_stat_1_label' => ['label' => 'Qué mide la cifra 1 (English)', 'max' => 60],
        'site_about_stat_2_label' => ['label' => 'Qué mide la cifra 2 (English)', 'max' => 60],
        'site_about_stat_3_label' => ['label' => 'Qué mide la cifra 3 (English)', 'max' => 60],
        'site_copyright' => ['label' => 'Texto de derechos (English)', 'max' => 150],
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $contact = SiteContent::contact();
        $social = SiteContent::social();
        $about = SiteContent::about();
        $hero = config('site.hero');

        $state = [
            'site_name' => SiteContent::name(),
            'site_logo' => Setting::get('site_logo'),
            'site_favicon' => Setting::get('site_favicon'),
            'site_brand_color' => SiteContent::brandColor(),
            'site_phone' => $contact['phone'],
            'site_whatsapp' => $contact['whatsapp'],
            'site_email' => $contact['email'],
            'site_address' => $contact['address'],
            'site_hours' => $contact['hours'],
            'site_hero_image' => Setting::get('site_hero_image'),
            'site_hero_badge' => Setting::get('site_hero_badge', $hero['badge']),
            'site_hero_title' => Setting::get('site_hero_title', $hero['title']),
            'site_hero_subtitle' => Setting::get('site_hero_subtitle', $hero['subtitle']),
            'site_about_title' => $about['title'],
            'site_about_text' => $about['text'],
            'site_facebook' => $social['facebook'],
            'site_instagram' => $social['instagram'],
            'site_copyright' => Setting::get('site_copyright'),
            'site_default_currency' => Setting::get('site_default_currency', Currency::default()?->title),
            'site_locales' => Lang::enabled(),
        ];

        foreach (array_keys(self::TRANSLATABLE) as $key) {
            $state[$key . '_en'] = Setting::get($key . '_en');
        }

        foreach ([1, 2, 3] as $n) {
            $state["site_about_stat_{$n}_number"] = $about['stats'][$n - 1]['number'];
            $state["site_about_stat_{$n}_label"] = $about['stats'][$n - 1]['label'];
            $state["site_about_stat_{$n}_image"] = Setting::get("site_about_stat_{$n}_image");
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $image = fn (string $name, string $label) => FileUpload::make($name)
            ->label($label)
            ->disk('media')
            ->directory('site')
            ->visibility('public')
            ->image()
            ->maxSize(8192);

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Tu negocio')
                    ->description('Aparece en la cabecera, el pie de página, los correos y los contratos.')
                    ->columns(['default' => 1, 'sm' => 2])
                    ->schema([
                        TextInput::make('site_name')->label('Nombre del negocio')->required()->maxLength(120)->columnSpanFull(),
                        TextInput::make('site_phone')->label('Teléfono')->tel()->maxLength(40)->placeholder('+1 (809) 555-0100'),
                        TextInput::make('site_whatsapp')->label('WhatsApp')->tel()->maxLength(40)->helperText('Con el código del país, por ejemplo 18095550100. Activa los botones de WhatsApp de la web.'),
                        TextInput::make('site_email')->label('Correo')->email()->maxLength(120),
                        TextInput::make('site_hours')->label('Horario')->maxLength(120),
                        TextInput::make('site_address')->label('Dirección')->maxLength(255)->columnSpanFull(),
                    ]),

                Section::make('Marca')
                    ->description('Logo y color principal de la web (botones y detalles).')
                    ->columns(['default' => 1, 'sm' => 2])
                    ->schema([
                        $image('site_logo', 'Logo')->imageEditor(),
                        $image('site_favicon', 'Ícono de la pestaña (favicon)'),
                        ColorPicker::make('site_brand_color')->label('Color principal')->required()->regex('/^#[0-9a-fA-F]{6}$/'),
                    ]),

                Section::make('Portada')
                    ->description('Imagen y textos grandes del inicio de la web.')
                    ->columns(2)
                    ->schema([
                        $image('site_hero_image', 'Imagen de portada')
                            ->imageEditor()
                            ->helperText('Recomendado: foto horizontal de al menos 1920 × 1080 px.')
                            ->columnSpanFull(),
                        TextInput::make('site_hero_badge')->label('Etiqueta')->maxLength(80),
                        TextInput::make('site_hero_title')->label('Título')->required()->maxLength(120),
                        Textarea::make('site_hero_subtitle')->label('Subtítulo')->rows(3)->maxLength(300)->columnSpanFull(),
                    ]),

                Section::make('Sobre nosotros')
                    ->description('Texto de la página "Sobre nosotros" y las tres cifras de la cabecera.')
                    ->collapsible()
                    ->columns(['default' => 1, 'sm' => 2])
                    ->schema([
                        TextInput::make('site_about_title')->label('Título')->maxLength(150)->columnSpanFull(),
                        Textarea::make('site_about_text')->label('Texto')->rows(4)->maxLength(1500)->columnSpanFull(),
                        TextInput::make('site_about_stat_1_number')->label('Cifra 1')->maxLength(12),
                        TextInput::make('site_about_stat_1_label')->label('Qué mide la cifra 1')->maxLength(60),
                        TextInput::make('site_about_stat_2_number')->label('Cifra 2')->maxLength(12),
                        TextInput::make('site_about_stat_2_label')->label('Qué mide la cifra 2')->maxLength(60),
                        TextInput::make('site_about_stat_3_number')->label('Cifra 3')->maxLength(12),
                        TextInput::make('site_about_stat_3_label')->label('Qué mide la cifra 3')->maxLength(60),
                        $image('site_about_stat_1_image', 'Foto de "Sobre nosotros" 1')->helperText('También es el fondo de la cabecera de Sobre nosotros.'),
                        $image('site_about_stat_2_image', 'Foto de "Sobre nosotros" 2')->helperText('También es el fondo de la cabecera de Preguntas frecuentes.'),
                    ]),

                Section::make('Redes sociales y pie de página')
                    ->collapsible()
                    ->columns(['default' => 1, 'sm' => 2])
                    ->schema([
                        TextInput::make('site_facebook')->label('Facebook')->url()->maxLength(255)->placeholder('https://www.facebook.com/tunegocio'),
                        TextInput::make('site_instagram')->label('Instagram')->url()->maxLength(255)->placeholder('https://www.instagram.com/tunegocio'),
                        TextInput::make('site_copyright')->label('Texto de derechos')->maxLength(150)->placeholder('Tu negocio. Todos los derechos reservados.')->columnSpanFull(),
                    ]),

                Section::make('Idiomas')
                    ->description('Idiomas en que se muestra la web. Con más de uno aparece el selector ES | EN en la barra superior, y cada idioma tiene su propia dirección (/en/...) para Google.')
                    ->schema([
                        CheckboxList::make('site_locales')
                            ->hiddenLabel()
                            ->options(Lang::SUPPORTED)
                            ->helperText('El español siempre está activo: es el idioma base de la web.')
                            ->default([Lang::DEFAULT])
                            ->columns(2),
                    ]),

                TranslationSection::make(self::TRANSLATABLE, 'Textos de la web en inglés (English)', fn (string $name) => $name . '_en')
                    ->description('Se muestran cuando el visitante elige English. Si un campo queda vacío se usa el texto en español (o su traducción por defecto).'),

                Section::make('Moneda')
                    ->schema([
                        Select::make('site_default_currency')
                            ->label('Moneda que ven los clientes por defecto')
                            ->options(fn () => Currency::query()->orderBy('order')->pluck('title', 'title'))
                            ->selectablePlaceholder(false)
                            ->required()
                            ->helperText('El cliente puede cambiarla en la barra superior de la web. Las reservas siempre se registran en la moneda base. Las tasas se ajustan en Configuración → Monedas.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Guardar')->submit('save'),
                        Action::make('view')
                            ->label('Ver la web')
                            ->color('gray')
                            ->url(route('site.home'))
                            ->openUrlInNewTab(),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $data = collect($state)->only(self::KEYS)->map(fn ($value) => $value ?? '');
        $data->put('site_locales', implode(',', array_unique([Lang::DEFAULT, ...array_intersect(array_keys(Lang::SUPPORTED), (array) ($state['site_locales'] ?? []))])));

        Setting::put($data->all());
        SeoFiles::writeRobots(url('/'));

        Notification::make()
            ->success()
            ->title('Web pública actualizada')
            ->body('Los cambios ya se ven en la web.')
            ->send();
    }
}
