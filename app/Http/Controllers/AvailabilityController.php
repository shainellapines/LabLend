<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityRequest;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * GET /reservations/availability?start_at=…&end_at=… (JSON).
 * The reservation form calls this whenever the dates change, so users see conflicts BEFORE submitting.
 * The real check still runs on submit (ReservationService) — this is only a helpful preview.
 * An "invokable" controller has a single __invoke() method, for a controller that does exactly one thing.
 */
class AvailabilityController extends Controller
{
    public function __invoke(AvailabilityRequest $request, ReservationService $reservations): JsonResponse
    {
        return response()->json($reservations->unavailableBetween(
            Carbon::parse($request->validated('start_at')),
            Carbon::parse($request->validated('end_at')),
            $request->validated('ignore') ? (int) $request->validated('ignore') : null,
        ));
    }
}
