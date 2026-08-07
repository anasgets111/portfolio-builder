<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use App\Models\SiteSetting;
use App\Support\PortfolioMetadata;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                        Tab::make('Homepage')
                            ->icon(Heroicon::OutlinedHome)
                            ->schema([
                                Section::make('Identity')
                                    ->description('Your name, role, and portrait across the portfolio.')
                                    ->schema([
                                        TextInput::make('name')
                                            ->maxLength(255)
                                            ->live(debounce: 400),
                                        TextInput::make('professional_title')
                                            ->maxLength(255)
                                            ->live(debounce: 400),
                                        FileUpload::make('profile_image')
                                            ->image()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->disk('public')
                                            ->directory('site/profile-images')
                                            ->visibility('public')
                                            ->maxSize(2048)
                                            ->rule(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))
                                            ->helperText('JPEG, PNG, or WebP. Maximum 2 MB and 2000 × 2000 pixels. Uploaded files are served as-is.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                Section::make('Hero')
                                    ->description('The introduction visitors see first.')
                                    ->schema([
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
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('About')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Section::make('About section')
                                    ->description('Tell visitors about your background, approach, and strengths.')
                                    ->schema([
                                        RichEditor::make('about_content')
                                            ->toolbarButtons([
                                                ['bold', 'italic', 'link'],
                                                ['h2', 'h3'],
                                                ['blockquote', 'bulletList', 'orderedList'],
                                                ['undo', 'redo'],
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Contact')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema([
                                Section::make('Contact section')
                                    ->description('The message shown before visitors choose how to reach you.')
                                    ->schema([
                                        RichEditor::make('contact_content')
                                            ->toolbarButtons([
                                                ['bold', 'italic', 'link'],
                                                ['bulletList', 'orderedList'],
                                                ['undo', 'redo'],
                                            ]),
                                    ]),
                                Section::make('Contact details and documents')
                                    ->schema([
                                        TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
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
                        Tab::make('Appearance')
                            ->icon(Heroicon::OutlinedSwatch)
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
                        Tab::make('SEO & Sharing')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        Group::make([
                                            Section::make('Publishing and indexing')
                                                ->description('Control the public origin, language, and crawler access for this portfolio.')
                                                ->schema([
                                                    Toggle::make('is_indexable')
                                                        ->label('Allow search engine indexing')
                                                        ->helperText('Keep this off until the portfolio is deployed at its final production URL.')
                                                        ->default(false)
                                                        ->required()
                                                        ->live(),
                                                    TextEntry::make('configured_application_url')
                                                        ->label('Application URL')
                                                        ->state(fn (): string => PortfolioMetadata::configuredApplicationUrl() ?? 'APP_URL is invalid')
                                                        ->helperText('Read from APP_URL for this deployment. It is used for canonical URLs, the sitemap, and social images.'),
                                                    TextInput::make('site_locale')
                                                        ->label('Site language')
                                                        ->placeholder('en')
                                                        ->helperText('Use a BCP 47 language tag such as en, en-GB, or ar-EG.')
                                                        ->default(str_replace('_', '-', app()->getLocale()))
                                                        ->required()
                                                        ->regex('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/')
                                                        ->maxLength(35)
                                                        ->live(debounce: 400),
                                                ]),
                                            Section::make('Search result')
                                                ->description('Optional defaults for search results. Public content supplies sensible fallbacks when these are blank.')
                                                ->schema([
                                                    TextInput::make('seo_title')
                                                        ->label('SEO title')
                                                        ->helperText('Keep it concise and descriptive. Search engines may rewrite titles to match a query.')
                                                        ->maxLength(255)
                                                        ->live(debounce: 400),
                                                    Textarea::make('seo_description')
                                                        ->label('SEO description')
                                                        ->helperText('Summarize the portfolio for potential visitors. The hero description is used when this is blank.')
                                                        ->rows(4)
                                                        ->live(debounce: 500),
                                                ]),
                                            Section::make('Social sharing')
                                                ->description('Defaults used when the portfolio is shared on social and messaging platforms.')
                                                ->schema([
                                                    FileUpload::make('og_image')
                                                        ->label('Social preview image')
                                                        ->image()
                                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                        ->disk('public')
                                                        ->directory('site/seo')
                                                        ->visibility('public')
                                                        ->maxSize(5120)
                                                        ->rule(Rule::dimensions()
                                                            ->minWidth(600)
                                                            ->minHeight(315)
                                                            ->maxWidth(2560)
                                                            ->maxHeight(1440)
                                                            ->minRatio(1.8)
                                                            ->maxRatio(2.0))
                                                        ->helperText('JPEG, PNG, or WebP. Use approximately 1.91:1, ideally 1200 × 630 pixels. Maximum 5 MB.'),
                                                    TextInput::make('twitter_handle')
                                                        ->label('X / Twitter handle')
                                                        ->prefix('@')
                                                        ->helperText('Optional creator attribution. Enter the handle without @.')
                                                        ->regex('/^[A-Za-z0-9_]{1,15}$/')
                                                        ->maxLength(15)
                                                        ->live(debounce: 400),
                                                ]),
                                        ])->columnSpan([
                                            'default' => 1,
                                            'xl' => 2,
                                        ]),
                                        View::make('filament.schemas.components.seo-preview')
                                            ->columnSpan([
                                                'default' => 1,
                                                'xl' => 3,
                                            ]),
                                    ]),
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
