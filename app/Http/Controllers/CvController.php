<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvController extends Controller
{
    private const string DISK = 'public';

    private const string FILENAME = 'Portfolio-CV.pdf';

    private const string MANAGED_DIRECTORY = 'site/resumes/';

    /**
     * Display the currently configured CV.
     */
    public function __invoke(): StreamedResponse
    {
        $resumeFile = SiteSetting::current()?->resume_file;

        abort_unless(
            is_string($resumeFile)
            && $this->isManagedPdf($resumeFile),
            404,
        );

        $disk = Storage::disk(self::DISK);

        abort_unless($disk->exists($resumeFile), 404);

        return $disk->response(
            $resumeFile,
            self::FILENAME,
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    private function isManagedPdf(string $path): bool
    {
        if (! Str::startsWith($path, self::MANAGED_DIRECTORY)) {
            return false;
        }

        $filename = Str::after($path, self::MANAGED_DIRECTORY);

        return $filename !== ''
            && ! Str::contains($filename, ['/', '\\'])
            && Str::endsWith(Str::lower($filename), '.pdf');
    }
}
