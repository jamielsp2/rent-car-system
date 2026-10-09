<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\CustomerStatus;
use App\Enums\LicenseStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Datos del cliente')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required()->maxLength(191)->columnSpanFull(),
                        TextInput::make('email')->label('Correo')->email()->required()->maxLength(191),
                        TextInput::make('phone')->label('Teléfono')->tel()->maxLength(30),
                        TextInput::make('whatsapp')->label('WhatsApp')->tel()->maxLength(25),
                        DatePicker::make('dob')->label('Fecha de nacimiento'),
                        TextInput::make('address')->label('Dirección')->maxLength(255)->columnSpanFull(),
                        Select::make('status')->label('Estado de la cuenta')->options(CustomerStatus::class)->required()->live(),
                        Textarea::make('block_reason')
                            ->label('Motivo del bloqueo')
                            ->rows(2)
                            ->visible(fn (Get $get) => ($get('status') instanceof CustomerStatus ? $get('status') : CustomerStatus::tryFrom((string) $get('status'))) !== CustomerStatus::Activated),
                    ]),

                Section::make('Licencia de conducir')
                    ->columns(2)
                    ->schema([
                        TextInput::make('license_number')->label('Número')->maxLength(60),
                        TextInput::make('license_region')->label('País / región')->maxLength(120),
                        DatePicker::make('license_expiry_date')->label('Vence'),
                        Select::make('license_status')->label('Revisión')->options(LicenseStatus::class)->live(),
                        Textarea::make('license_rejected_reason')
                            ->label('Motivo del rechazo')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => in_array($get('license_status'), [LicenseStatus::Rejected, LicenseStatus::Rejected->value], true)),
                    ]),
            ]);
    }
}
