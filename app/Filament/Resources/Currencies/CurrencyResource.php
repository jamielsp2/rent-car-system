<?php

namespace App\Filament\Resources\Currencies;

use App\Filament\Resources\Currencies\Pages\ManageCurrencies;
use App\Models\Currency;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Currencies offered on the public site. The base currency (USD) holds every
 * stored amount; the others only convert prices for display.
 */
class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'moneda';

    protected static ?string $pluralModelLabel = 'monedas';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        $base = Currency::default();

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Código')
                    ->helperText('Tres letras, por ejemplo USD, DOP o EUR.')
                    ->required()
                    ->length(3)
                    ->alpha()
                    ->dehydrateStateUsing(fn (string $state) => strtoupper($state))
                    ->unique(ignoreRecord: true),
                TextInput::make('symbol')->label('Símbolo')->required()->maxLength(10),
                TextInput::make('exchange_rate')
                    ->label('Tasa de cambio')
                    ->helperText(fn (?Currency $record) => $record?->is_default
                        ? 'Es la moneda base: todos los precios se guardan en ella. Su tasa siempre es 1.'
                        : 'Cuántas unidades de esta moneda equivalen a 1 ' . ($base?->title ?? 'USD') . '.')
                    ->numeric()
                    ->minValue(0.000001)
                    ->required()
                    ->disabled(fn (?Currency $record) => (bool) $record?->is_default)
                    ->prefix('1 ' . ($base?->title ?? 'USD') . ' ='),
                TextInput::make('decimals')->label('Decimales')->numeric()->minValue(0)->maxValue(4)->default(2)->required(),
                Toggle::make('is_prefix_symbol')->label('Símbolo antes del número')->default(true),
                Toggle::make('space_between_price_and_currency')->label('Espacio entre símbolo y número'),
                TextInput::make('order')->label('Orden')->numeric()->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                TextColumn::make('title')->label('Moneda')->weight('bold')
                    ->description(fn (Currency $record) => $record->is_default ? 'Moneda base' : null),
                TextColumn::make('symbol')->label('Símbolo'),
                TextColumn::make('exchange_rate')
                    ->label('Tasa')
                    ->formatStateUsing(fn (Currency $record) => $record->is_default
                        ? '1 (base)'
                        : '1 ' . (Currency::default()?->title ?? 'USD') . ' = ' . rtrim(rtrim(number_format($record->exchange_rate, 6), '0'), '.') . ' ' . $record->title),
                TextColumn::make('example')
                    ->label('Ejemplo: $100 USD')
                    ->state(fn (Currency $record) => $record->formatWithCode($record->convert(100))),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Currency $record) => $record->is_default),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCurrencies::route('/'),
        ];
    }
}
