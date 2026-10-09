<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    // Every account is a full administrator; the username is only an internal unique key.
                    $data['super_user'] = true;
                    $data['username'] = $this->uniqueUsername($data['email']);

                    return $data;
                }),
        ];
    }

    protected function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: 'usuario';
        $username = $base;

        for ($i = 2; User::query()->where('username', $username)->exists(); $i++) {
            $username = "{$base}{$i}";
        }

        return $username;
    }
}
