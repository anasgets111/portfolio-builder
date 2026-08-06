<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Kholil\FilamentAnalitik\Models\PageView;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

class TrackPortfolioPageView
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('filament-analitik.enabled', true)
            || ! $response->isSuccessful()
            || $request->header('DNT') === '1'
            || $request->header('Sec-GPC') === '1') {
            return $response;
        }

        $pageView = [
            'url' => $request->url(),
            'path' => '/'.ltrim($request->path(), '/'),
            'method' => $request->method(),
            'ip' => $this->anonymousVisitorId($request),
            'user_agent' => null,
            'city' => null,
            'state' => null,
            'country' => $this->countryName($request),
            'project_id' => null,
        ];

        defer(fn () => PageView::query()->create($pageView));

        return $response;
    }

    private function anonymousVisitorId(Request $request): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [$request->ip() ?? '', $request->userAgent() ?? '']),
            Config::string('app.key'),
        );
    }

    private function countryName(Request $request): ?string
    {
        $headers = config('filament-analitik.country_headers', []);

        if (! is_array($headers)) {
            return null;
        }

        foreach ($headers as $header) {
            if (! is_string($header)) {
                continue;
            }

            $headerValue = $request->header($header);

            if (! is_string($headerValue)) {
                continue;
            }

            $countryCode = Str::upper($headerValue);

            if (! Str::isMatch('/^[A-Z]{2}$/', $countryCode) || $countryCode === 'XX') {
                continue;
            }

            if (! class_exists(\Locale::class)) {
                return $countryCode;
            }

            return \Locale::getDisplayRegion('-'.$countryCode, 'en') ?: $countryCode;
        }

        return null;
    }
}
