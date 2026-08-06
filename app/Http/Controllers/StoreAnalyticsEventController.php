<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnalyticsEventRequest;
use App\Models\AnalyticsEvent;
use Illuminate\Http\Response;

use function Illuminate\Support\defer;

class StoreAnalyticsEventController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreAnalyticsEventRequest $request): Response
    {
        if (! config('filament-analitik.enabled', true)) {
            return response()->noContent();
        }

        defer(fn () => AnalyticsEvent::query()->create($request->validated()));

        return response()->noContent();
    }
}
