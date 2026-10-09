<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Post;
use App\Support\Media;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->state(fn (Post $record) => Media::url($record->image))
                    ->imageHeight(44),
                TextColumn::make('name')->label('Título')->searchable()->wrap()->weight('bold'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'published' ? 'Publicado' : 'Borrador')
                    ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),
                TextColumn::make('views')->label('Visitas')->numeric()->sortable()->alignEnd(),
                TextColumn::make('created_at')->label('Fecha')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(['published' => 'Publicado', 'draft' => 'Borrador']),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Ver')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn (Post $record) => $record->status === 'published' && $record->slug)
                    ->url(fn (Post $record) => $record->url)
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
