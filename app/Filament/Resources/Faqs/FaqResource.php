<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'pregunta frecuente';

    protected static ?string $pluralModelLabel = 'preguntas frecuentes';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Textarea::make('question')->label('Pregunta')->rows(2)->required()->columnSpanFull(),
                RichEditor::make('answer')->label('Respuesta')->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']])->required()->columnSpanFull(),
                Select::make('status')->label('Estado')->options(['published' => 'Publicado', 'draft' => 'Borrador'])->default('published')->selectablePlaceholder(false)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('question')->label('Pregunta')->weight('bold')->limit(90)->searchable()->wrap(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => $state === 'published' ? 'Publicado' : 'Borrador')->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Faq $record) => ! (true)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqs::route('/'),
        ];
    }
}
