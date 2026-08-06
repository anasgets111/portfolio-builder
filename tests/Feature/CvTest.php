<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

it('serves the configured CV inline with a stable filename', function () {
    Storage::fake('public');

    $pdfContents = '%PDF-1.7 configured portfolio CV';
    $resumeFile = 'site/resumes/current-cv.pdf';

    Storage::disk('public')->put($resumeFile, $pdfContents);
    SiteSetting::factory()->create(['resume_file' => $resumeFile]);

    $response = $this->get(route('cv.show'));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertStreamedContent($pdfContents);

    expect($response->headers->get('Content-Disposition'))
        ->toContain('inline', 'Portfolio-CV.pdf');
});

it('returns not found when no CV is configured', function () {
    Storage::fake('public');
    SiteSetting::factory()->create(['resume_file' => null]);

    $this->get(route('cv.show'))->assertNotFound();
});

it('returns not found when the configured CV does not exist', function () {
    Storage::fake('public');
    SiteSetting::factory()->create(['resume_file' => 'site/resumes/missing.pdf']);

    $this->get(route('cv.show'))->assertNotFound();
});

it('does not serve configured files outside the managed CV directory', function () {
    Storage::fake('public');
    Storage::disk('public')->put('projects/private.pdf', '%PDF-1.7');
    $siteSetting = SiteSetting::factory()->create(['resume_file' => 'projects/private.pdf']);

    $this->get(route('cv.show'))->assertNotFound();

    $siteSetting->update(['resume_file' => 'site/resumes/../private.pdf']);

    $this->get(route('cv.show'))->assertNotFound();
});
