<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Resources\Pages\CreateRecord;

/**
 * @property Post $record
 */
class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function afterCreate(): void
    {
        $this->record->syncSlug($this->form->getRawState()['slug_key'] ?? null);
    }

    protected function getRedirectUrl(): string
    {
        return PostResource::getUrl('edit', ['record' => $this->record]);
    }
}
