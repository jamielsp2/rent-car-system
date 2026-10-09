<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Post $record
 */
class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Ver en la web')
                ->color('gray')
                ->visible(fn () => $this->record->status === 'published' && $this->record->slug)
                ->url(fn () => $this->record->url)
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->syncSlug($this->form->getRawState()['slug_key'] ?? null);
    }
}
