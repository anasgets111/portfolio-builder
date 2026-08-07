<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\PortfolioMark;
use Illuminate\Http\Response;

class FaviconController extends Controller
{
    public function __invoke(): Response
    {
        $portfolioMark = new PortfolioMark(SiteSetting::current());

        return response()->view(
            'favicon',
            [
                'initials' => $portfolioMark->initials(),
                'colors' => $portfolioMark->colors(),
            ],
            headers: [
                'Content-Type' => 'image/svg+xml',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
