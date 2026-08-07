<?php

namespace App\Filament\Resources\SiteSettings\Pages;

use App\Filament\Resources\SiteSettings\SiteSettingResource;
use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSiteSetting extends EditRecord
{
    protected static string $resource = SiteSettingResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewPortfolio')
                ->label('Open portfolio')
                ->icon('fas-arrow-up-right-from-square')
                ->url(route('home'))
                ->openUrlInNewTab(),
            Action::make('restoreAppearanceDefaults')
                ->label('Reset appearance')
                ->icon('fas-rotate-left')
                ->color('gray')
                ->modalHeading('Reset appearance controls?')
                ->modalDescription('The current portfolio design will be loaded into the form. It will not be published until you save.')
                ->modalSubmitActionLabel('Load defaults')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->data['appearance'] = SiteSetting::DEFAULT_APPEARANCE;
                    $this->data['appearance_theme'] = null;
                    $this->data['color_theme'] = null;

                    Notification::make()
                        ->title('Default appearance loaded')
                        ->body('Save the form to publish it.')
                        ->info()
                        ->send();
                }),
        ];
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['appearance'] = SiteSetting::resolveAppearance($data['appearance'] ?? null);

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['appearance'] = SiteSetting::resolveAppearance($data['appearance'] ?? null);

        return $data;
    }
}
