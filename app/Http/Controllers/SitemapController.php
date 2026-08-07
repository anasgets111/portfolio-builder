<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\PortfolioMetadata;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $metadata = new PortfolioMetadata(SiteSetting::current());

        abort_unless($metadata->isIndexable(), 404);

        return response()->view('sitemap', [
            'url' => $metadata->canonicalUrl(),
        ], headers: [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
