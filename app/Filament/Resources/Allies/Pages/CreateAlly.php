<?php

namespace App\Filament\Resources\Allies\Pages;

use App\Filament\Resources\Allies\AllyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlly extends CreateRecord
{
    protected static string $resource = AllyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Allies are not staff: no role and no super-admin flag.
        return [...$data, 'is_ally' => true, 'super_user' => false, 'role_id' => null];
    }

    protected function getRedirectUrl(): string
    {
        return AllyResource::getUrl('edit', ['record' => $this->record]);
    }
}
