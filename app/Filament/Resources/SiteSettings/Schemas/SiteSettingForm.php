<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use App\Models\SiteSetting;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Site settings')
                    ->tabs([
                        Tab::make('Profile')
                            ->icon('fas-id-card')
                            ->schema([
                                Section::make('Identity and hero')
                                    ->description('The introduction visitors see first.')
                                    ->schema([
                                        TextInput::make('name')
                                            ->maxLength(255)
                                            ->live(debounce: 400),
                                        TextInput::make('professional_title')
                                            ->maxLength(255)
                                            ->live(debounce: 400),
                                        TextInput::make('hero_heading')
                                            ->maxLength(255)
                                            ->live(debounce: 400),
                                        TextInput::make('hero_subheading')
                                            ->maxLength(255)
                                            ->live(debounce: 400),
                                        Textarea::make('hero_description')
                                            ->rows(4)
                                            ->live(debounce: 500)
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
                            ]),
                        Tab::make('Content')
                            ->icon('fas-file-lines')
                            ->schema([
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
                            ]),
                        Tab::make('Design')
                            ->icon('fas-palette')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'xl' => 5,
                                ])->schema([
                                    Group::make([
                                        Section::make('Style theme')
                                            ->description('Apply a complete starting style, then fine-tune any option below.')
                                            ->schema([
                                                Select::make('appearance_theme')
                                                    ->label('Style theme')
                                                    ->options(SiteSetting::APPEARANCE_THEME_OPTIONS)
                                                    ->placeholder('Custom / current design')
                                                    ->native(false)
                                                    ->dehydrated(false)
                                                    ->live()
                                                    ->afterStateUpdated(function (?string $state, Set $set): void {
                                                        if ($state === null) {
                                                            return;
                                                        }

                                                        $theme = SiteSetting::APPEARANCE_THEMES[$state] ?? null;

                                                        if (is_array($theme)) {
                                                            $set('color_theme', null);
                                                            $set('appearance', $theme);
                                                        }
                                                    }),
                                            ]),
                                        Section::make('Color')
                                            ->description('Apply a familiar palette or edit each color independently. Readability rules still apply.')
                                            ->schema([
                                                Select::make('color_theme')
                                                    ->label('Color theme')
                                                    ->options(SiteSetting::APPEARANCE_COLOR_THEME_OPTIONS)
                                                    ->placeholder('Custom colors')
                                                    ->native(false)
                                                    ->dehydrated(false)
                                                    ->live()
                                                    ->afterStateUpdated(function (?string $state, Set $set): void {
                                                        if ($state === null) {
                                                            return;
                                                        }

                                                        $colors = SiteSetting::APPEARANCE_COLOR_THEMES[$state] ?? null;

                                                        if (is_array($colors)) {
                                                            $set('appearance_theme', null);
                                                            $set('appearance.colors', $colors);
                                                        }
                                                    }),
                                                self::colorPicker('canvas', 'Page background'),
                                                self::colorPicker('panel', 'Panel background'),
                                                self::colorPicker('ink', 'Primary text', [
                                                    'Text must have at least 4.5:1 contrast against the page background.',
                                                    'Text must have at least 4.5:1 contrast against panels.',
                                                ]),
                                                self::colorPicker('ink_muted', 'Muted text', [
                                                    'Muted text must have at least 4.5:1 contrast against the page background.',
                                                ]),
                                                self::colorPicker('brand', 'Accent', [
                                                    'The accent must have at least 4.5:1 contrast against the page background.',
                                                ]),
                                                self::colorPicker('brand_soft', 'Soft accent', [
                                                    'The soft accent must have at least 4.5:1 contrast against the page background.',
                                                ]),
                                            ])
                                            ->columns(2),
                                        Section::make('Canvas')
                                            ->schema([
                                                Select::make('appearance.font')
                                                    ->label('Typeface')
                                                    ->options(SiteSetting::APPEARANCE_OPTIONS['font'])
                                                    ->required()
                                                    ->native(false)
                                                    ->live(),
                                                self::toggle('color_scheme', 'Browser controls'),
                                                self::toggle('page_width', 'Page width'),
                                                self::toggle('corner_style', 'Corners'),
                                            ])
                                            ->columns(2),
                                        Section::make('Layout and behavior')
                                            ->schema([
                                                self::toggle('hero_layout', 'Hero'),
                                                self::toggle('project_layout', 'Projects'),
                                                self::toggle('motion', 'Motion'),
                                            ])
                                            ->columns(2),
                                    ])->columnSpan([
                                        'default' => 1,
                                        'xl' => 2,
                                    ]),
                                    View::make('filament.schemas.components.appearance-preview')
                                        ->columnSpan([
                                            'default' => 1,
                                            'xl' => 3,
                                        ]),
                                ]),
                            ]),
                        Tab::make('SEO')
                            ->icon('fas-magnifying-glass')
                            ->schema([
                                Section::make('Search and sharing')
                                    ->description('Metadata used by search engines and social previews.')
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
                            ]),
                    ])
                    ->persistTabInQueryString('settings-tab')
                    ->columnSpanFull(),
            ]);
    }

    /** @param list<string> $failureMessages */
    private static function colorPicker(string $name, string $label, array $failureMessages = []): ColorPicker
    {
        return ColorPicker::make("appearance.colors.{$name}")
            ->label($label)
            ->required()
            ->regex('/^#[0-9a-f]{6}$/i')
            ->rules(array_map(self::contrastRule(...), $failureMessages))
            ->live(debounce: 300);
    }

    private static function toggle(string $name, string $label): ToggleButtons
    {
        return ToggleButtons::make("appearance.{$name}")
            ->label($label)
            ->options(SiteSetting::APPEARANCE_OPTIONS[$name])
            ->required()
            ->grouped()
            ->live();
    }

    private static function contrastRule(string $failureMessage): Closure
    {
        return fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $failureMessage): void {
            $colors = [];
            $currentColor = Str::afterLast($attribute, '.');

            foreach (array_keys(SiteSetting::DEFAULT_APPEARANCE['colors']) as $colorName) {
                $colors[$colorName] = $colorName === $currentColor
                    ? $value
                    : $get("appearance.colors.{$colorName}");
            }

            if (in_array($failureMessage, SiteSetting::appearanceContrastFailures($colors), true)) {
                $fail($failureMessage);
            }
        };
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
