<?php

namespace App\Filament\Resources\Cars\RelationManagers;

use App\Support\LocalTime;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Costs of a vehicle; they reduce its profitability.
 */
class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Gastos del vehículo';

    protected static ?string $modelLabel = 'gasto';

    protected static ?string $pluralModelLabel = 'gastos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Concepto')
                    ->placeholder('Cambio de aceite, gomas, seguro, reparación...')
                    ->required()
                    ->maxLength(191)
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Monto')
                    ->helperText('En la moneda base (USD).')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->required(),
                DatePicker::make('date')
                    ->label('Fecha')
                    ->default(LocalTime::today())
                    ->required(),
                Textarea::make('description')->label('Notas')->rows(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('name')->label('Concepto')->searchable()->wrap(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Money::format($state))),
            ])
            ->filters([
                Filter::make('this_year')
                    ->label('Solo ' . LocalTime::now()->year)
                    ->default()
                    ->query(fn (Builder $query) => $query->whereYear('date', LocalTime::now()->year)),
            ])
            ->headerActions([
                CreateAction::make()->label('Registrar gasto'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
