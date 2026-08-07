<?php

namespace App\Filament\Pages;

use App\Actions\ExportPortfolioBackup;
use App\Actions\RestorePortfolioBackup;
use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PortfolioBackups extends Page
{
    use RestrictsFileUploadsToSchemaComponents;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Backups';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.portfolio-backups';

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadBackup')
                ->label('Download backup')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->failureNotificationTitle('Backup could not be created')
                ->action(function (Action $action, ExportPortfolioBackup $export): ?BinaryFileResponse {
                    try {
                        $archivePath = $export->handle();
                    } catch (Throwable $exception) {
                        report($exception);
                        $action->failure();

                        return null;
                    }

                    $siteSetting = SiteSetting::current();
                    $ownerName = $siteSetting?->name;
                    $firstName = $ownerName === null
                        ? ''
                        : Str::before(Str::slug($ownerName), '-');
                    $filenamePrefix = $firstName === '' ? '' : $firstName.'-';

                    return response()
                        ->download(
                            $archivePath,
                            $filenamePrefix.'portfolio-backup-'.now()->format('Y-m-d').'.zip',
                            ['Content-Type' => 'application/zip'],
                        )
                        ->deleteFileAfterSend(true);
                }),
            Action::make('restoreBackup')
                ->label('Restore backup')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('danger')
                ->modalHeading('Replace portfolio content?')
                ->modalDescription('Projects, experiences, relationships, skills, and site settings will be replaced by this backup. Administrator accounts and analytics are not affected.')
                ->modalSubmitActionLabel('Restore backup')
                ->requiresConfirmation()
                ->successNotificationTitle('Portfolio restored')
                ->failureNotificationTitle('Backup could not be restored')
                ->schema([
                    FileUpload::make('backup')
                        ->label('Portfolio backup ZIP')
                        ->acceptedFileTypes([
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/octet-stream',
                        ])
                        ->maxSize(RestorePortfolioBackup::MAX_ARCHIVE_KILOBYTES)
                        ->storeFiles(false)
                        ->visibility('private')
                        ->previewable(false)
                        ->required(),
                ])
                ->action(function (Action $action, array $data, RestorePortfolioBackup $restore): void {
                    $backup = $data['backup'] ?? null;

                    try {
                        if (! $backup instanceof TemporaryUploadedFile) {
                            throw new RuntimeException('Select a portfolio backup ZIP to restore.');
                        }

                        $restore->handle($backup->getRealPath());
                    } catch (Throwable $exception) {
                        report($exception);
                        $action->failure();

                        return;
                    }

                    $action->success();
                }),
        ];
    }
}
