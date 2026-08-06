<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity and hero')
                    ->schema([
                        TextInput::make('name')->maxLength(255),
                        TextInput::make('professional_title')->maxLength(255),
                        TextInput::make('hero_heading')->maxLength(255),
                        TextInput::make('hero_subheading')->maxLength(255),
                        Textarea::make('hero_description')
                            ->rows(4)
                            ->columnSpanFull(),
                        FileUpload::make('profile_image')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('site/profile-images')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->rule(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))
                            ->helperText('JPEG, PNG, or WebP. Maximum 2 MB and 2000 × 2000 pixels. Uploaded files are served as-is.'),
                    ])
                    ->columns(2),
                Section::make('About and contact')
                    ->schema([
                        RichEditor::make('about_content')
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['h2', 'h3'],
                                ['blockquote', 'bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                        RichEditor::make('contact_content')
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                        TextInput::make('email')->email()->maxLength(255),
                        FileUpload::make('resume_file')
                            ->label('CV')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('public')
                            ->directory('site/resumes')
                            ->visibility('public')
                            ->maxSize(10240)
                            ->helperText('PDF only. Maximum file size: 10 MB.'),
                    ])
                    ->columns(2),
                Section::make('Social links')
                    ->schema([
                        Repeater::make('social_links')
                            ->schema([
                                TextInput::make('platform')->required()->maxLength(100),
                                TextInput::make('label')->required()->maxLength(100),
                                TextInput::make('url')
                                    ->required()
                                    ->rules([fn (): Closure => self::socialLinkUrlRule()])
                                    ->maxLength(2048),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Search and sharing')
                    ->schema([
                        TextInput::make('site_url')->url()->maxLength(2048),
                        TextInput::make('seo_title')->maxLength(255),
                        Textarea::make('seo_description')->rows(4)->columnSpanFull(),
                        TagsInput::make('seo_keywords')->columnSpanFull(),
                        FileUpload::make('og_image')
                            ->image()
                            ->disk('public')
                            ->directory('site/seo')
                            ->visibility('public')
                            ->maxSize(5120),
                        TextInput::make('twitter_handle')
                            ->prefix('@')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    private static function socialLinkUrlRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                $fail('The :attribute must be a valid web, email, or telephone link.');

                return;
            }

            $isWebUrl = Str::startsWith($value, ['http://', 'https://'])
                && Str::isUrl($value, ['http', 'https']);
            $isEmailUrl = Str::startsWith($value, 'mailto:')
                && filter_var(Str::after($value, 'mailto:'), FILTER_VALIDATE_EMAIL) !== false;
            $telephone = Str::after($value, 'tel:');
            $isTelephoneUrl = Str::startsWith($value, 'tel:')
                && preg_match('/^\+?[0-9][0-9(). -]*$/', $telephone) === 1;

            if (! $isWebUrl && ! $isEmailUrl && ! $isTelephoneUrl) {
                $fail('The :attribute must be a valid web, email, or telephone link.');
            }
        };
    }
}
