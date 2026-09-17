<?php

namespace App\Services;

use App\Http\Resources\SoaAndBillingResource;
use App\Models\Booking;
use App\Models\StatementOfAccount;
use App\Models\WaybillDetail;
use Exception;

class FetchSoasByIdsService
{
    /**
     * Fetch SOA details for the given IDs and the combined gross amount.
     *
     * @param  array<int, int>  $soaIds
     * @return array{soa: array<int, array<string, mixed>>, total_gross: float}
     */
    public function fetchByIds(array $soaIds): array
    {
        $soaIds = array_values(array_unique(array_map('intval', $soaIds)));

        if (empty($soaIds)) {
            throw new Exception('At least one statement of account is required.');
        }

        $order = array_flip($soaIds);

        $soas = StatementOfAccount::with(['shippingLine', 'billingStatements', 'invoices'])
            ->whereIn('id', $soaIds)
            ->get()
            ->sortBy(fn (StatementOfAccount $soa) => $order[(int) $soa->id] ?? PHP_INT_MAX)
            ->values();

        if ($soas->count() !== count($soaIds)) {
            throw new Exception('One or more selected statements of account do not exist.');
        }

        $allBookingIds = $soas
            ->flatMap(fn (StatementOfAccount $soa) => $soa->booking_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->values()
            ->all();

        $bookingsById = empty($allBookingIds)
            ? collect()
            : Booking::whereIn('id', $allBookingIds)
                ->with('preparedByUser')
                ->withCount(['activeBookingContainers as containers_count'])
                ->get()
                ->keyBy(fn (Booking $booking) => (int) $booking->id);

        $waybillsByBookingId = empty($allBookingIds)
            ? collect()
            : WaybillDetail::whereIn('booking_id', $allBookingIds)
                ->get()
                ->groupBy(fn (WaybillDetail $waybill) => (int) $waybill->booking_id);

        foreach ($soas as $soa) {
            $bookingIds = array_map('intval', $soa->booking_ids ?? []);
            $soa->setRelation(
                'bookings',
                collect($bookingIds)
                    ->map(fn (int $id) => $bookingsById->get($id))
                    ->filter()
                    ->values()
            );
            $soa->setRelation(
                'waybills',
                collect($bookingIds)
                    ->flatMap(fn (int $id) => $waybillsByBookingId->get($id, collect()))
                    ->values()
            );
        }

        $soaData = SoaAndBillingResource::collection($soas)->resolve();
        $totalGross = collect($soaData)->sum(fn (array $item) => (float) ($item['total_amount'] ?? 0));

        return [
            'soa' => $soaData,
            'total_gross' => (float) number_format($totalGross, 2, '.', ''),
        ];
    }
}
