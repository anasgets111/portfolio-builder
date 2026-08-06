<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\TechnologySuggestions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(255),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Textarea::make('summary')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        RichEditor::make('body')
                            ->required()
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['h2', 'h3'],
                                ['blockquote', 'bulletList', 'orderedList', 'codeBlock'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->required()
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('projects')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->rule(Rule::dimensions()->maxWidth(2560)->maxHeight(1600))
                            ->helperText('JPEG, PNG, or WebP. Maximum 4 MB and 2560 × 1600 pixels. Uploaded files are served as-is.'),
                        TagsInput::make('technologies')
                            ->required()
                            ->suggestions(fn (): array => TechnologySuggestions::all())
                            ->columnSpanFull(),
                        TextInput::make('source_url')->url()->maxLength(2048),
                        TextInput::make('live_url')->url()->maxLength(2048),
                    ])
                    ->columns(2),
                Section::make('Publishing')
                    ->schema([
                        Toggle::make('is_published')
                            ->required()
                            ->default(false),
                        DateTimePicker::make('published_at')->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }
}
