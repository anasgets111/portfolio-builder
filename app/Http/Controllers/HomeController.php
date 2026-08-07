<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Support\PortfolioMark;
use App\Support\PortfolioMetadata;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Display the public portfolio.
     */
    public function __invoke(): View
    {
        $siteSetting = SiteSetting::current();
        $portfolioMark = new PortfolioMark($siteSetting);

        return view('home', [
            'siteSetting' => $siteSetting,
            'metadata' => new PortfolioMetadata($siteSetting),
            'logoInitials' => $portfolioMark->initials(),
            'faviconVersion' => $portfolioMark->fingerprint(),
            'projects' => Project::query()
                ->published()
                ->ordered()
                ->with('publishedExperiences')
                ->get(),
            'experiences' => Experience::query()
                ->published()
                ->ordered()
                ->with('publishedProjects')
                ->get(),
            'skills' => Skill::query()->published()->ordered()->get(),
        ]);
    }
}
