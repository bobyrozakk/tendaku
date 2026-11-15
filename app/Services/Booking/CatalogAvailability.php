<?php

namespace App\Services\Booking;

use Carbon\Carbon;
use Throwable;

class CatalogAvailability
{
    public function __construct(private AvailabilityService $availabilityService) {}

    /** @return array{start_date: string, end_date: string}|null */
    public function dateRange(?string $startDate = null, ?string $endDate = null): ?array
    {
        if (($startDate === null || $startDate === '') && ($endDate === null || $endDate === '')) {
            $startDate = Carbon::tomorrow()->toDateString();
            $endDate = Carbon::tomorrow()->addDay()->toDateString();
        }

        try {
            if (! $startDate || ! $endDate) {
                return null;
            }

            $start = Carbon::createFromFormat('!Y-m-d', $startDate);
            $end = Carbon::createFromFormat('!Y-m-d', $endDate);

            if (! $start || ! $end || $start->toDateString() !== $startDate || $end->toDateString() !== $endDate
                || $start->lt(Carbon::today()) || $end->lt($start)) {
                return null;
            }

            return ['start_date' => $startDate, 'end_date' => $endDate];
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array{start_date: string, end_date: string}|null $dateRange */
    public function stock(int $masterItemId, ?array $dateRange): ?int
    {
        if ($dateRange === null) {
            return null;
        }

        try {
            return $this->availabilityService->checkAvailability(
                $masterItemId, $dateRange['start_date'], $dateRange['end_date']
            );
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
