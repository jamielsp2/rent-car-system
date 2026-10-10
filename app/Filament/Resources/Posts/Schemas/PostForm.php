<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Support\SeoSection;
use App\Filament\Support\TranslationSection;
use App\Models\Post;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Artículo')
                        ->schema([
                            TextInput::make('name')
                                ->label('Título')
                                ->required()
                                ->maxLength(191)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, ?string $old, Set $set, callable $get): void {
                                    // Suggest the address from the title until it's edited by hand.
                                    if (blank($get('slug_key')) || $get('slug_key') === Str::slug((string) $old)) {
                                        $set('slug_key', Str::slug((string) $state));
                                    }
                                }),
                            TextInput::make('slug_key')
                                ->label('Dirección')
                                ->prefix(url('/news') . '/')
                                ->helperText('Se completa sola a partir del título.')
                                ->maxLength(191)
                                ->dehydrated(false)
                                ->afterStateHydrated(fn (TextInput $component, ?Post $record) => $component->state($record?->slug?->key)),
                            Textarea::make('description')
                                ->label('Resumen')
                                ->helperText('Aparece en la lista del blog y en Google.')
                                ->rows(3)
                                ->maxLength(400),
                            RichEditor::make('content')
                                ->label('Contenido')
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline', 'link'],
                                    ['h2', 'h3', 'blockquote'],
                                    ['bulletList', 'orderedList'],
                                    ['attachFiles'],
                                    ['undo', 'redo'],
                                ])
                                ->fileAttachmentsDisk('media')
                                ->fileAttachmentsDirectory('blog')
                                ->fileAttachmentsVisibility('public')
                                ->required(),
                        ]),

                    TranslationSection::make([
                        'name' => ['label' => 'Título (English)'],
                        'description' => ['label' => 'Resumen (English)', 'type' => 'textarea', 'rows' => 3, 'max' => 400],
                        'content' => ['label' => 'Contenido (English)', 'type' => 'rich', 'toolbar' => [['bold', 'italic', 'underline', 'link'], ['h2', 'h3', 'blockquote'], ['bulletList', 'orderedList'], ['undo', 'redo']]],
                    ]),

                    SeoSection::make(),
                ])->columnSpan(['default' => 3, 'lg' => 2]),

                Group::make([
                    Section::make('Publicar')
                        ->schema([
                            Select::make('status')
                                ->label('Estado')
                                ->options(['published' => 'Publicado', 'draft' => 'Borrador'])
                                ->default('published')
                                ->selectablePlaceholder(false)
                                ->required(),
                            Toggle::make('is_featured')->label('Destacado'),
                        ]),
                    Section::make('Imagen principal')
                        ->schema([
                            FileUpload::make('image')
                                ->hiddenLabel()
                                ->disk('media')
                                ->directory('blog')
                                ->visibility('public')
                                ->image()
                                ->imageEditor()
                                ->maxSize(8192),
                        ]),
                ])->columnSpan(['default' => 3, 'lg' => 1]),
            ]);
    }
}
