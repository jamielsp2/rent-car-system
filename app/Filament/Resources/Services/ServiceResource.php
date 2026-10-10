<?php

namespace App\Filament\Resources\Services;

use App\Enums\ServicePriceType;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Filament\Support\TranslationSection;
use App\Models\Service;
use App\Support\Media;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Extras customers can add when booking (driver, delivery, child seat...).
 */
class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $modelLabel = 'servicio adicional';

    protected static ?string $pluralModelLabel = 'servicios adicionales';

    protected static ?int $navigationSort = 5;

    protected const STATUSES = ['published' => 'Publicado', 'draft' => 'Oculto'];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(191)->columnSpanFull(),
                Textarea::make('description')->label('Descripción corta')->rows(2)->maxLength(500)->columnSpanFull(),
                TextInput::make('price')
                    ->label('Precio')
                    ->helperText('En la moneda base (USD).')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('$'),
                Select::make('price_type')
                    ->label('Se cobra')
                    ->options(ServicePriceType::class)
                    ->default(ServicePriceType::Once)
                    ->selectablePlaceholder(false)
                    ->required(),
                Select::make('status')
                    ->label('Estado')
                    ->options(self::STATUSES)
                    ->default('published')
                    ->selectablePlaceholder(false)
                    ->helperText('Solo los publicados aparecen en la web.')
                    ->required(),
                FileUpload::make('image')
                    ->label('Imagen (opcional)')
                    ->disk('media')
                    ->directory('services')
                    ->visibility('public')
                    ->image()
                    ->maxSize(4096),
                TranslationSection::make([
                    'name' => ['label' => 'Nombre (English)'],
                    'description' => ['label' => 'Descripción corta (English)', 'type' => 'textarea', 'max' => 500],
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('cover')->label('')->state(fn (Service $record) => Media::url($record->image))->imageHeight(36),
                TextColumn::make('name')->label('Servicio')->searchable()->weight('bold')->wrap(),
                TextColumn::make('price')->label('Precio')->formatStateUsing(fn ($state) => Money::format($state))->alignEnd()->sortable(),
                TextColumn::make('price_type')->label('Se cobra'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(self::STATUSES),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageServices::route('/'),
        ];
    }
}
