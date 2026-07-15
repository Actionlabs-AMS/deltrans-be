<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\StatementOfAccount;
use App\Models\WaybillDetail;
use App\Http\Resources\BookingResource;
use Illuminate\Support\Collection;

class BookingService extends BaseService
{
    public function __construct()
    {
        // Pass the BookingResource class to the parent constructor
        parent::__construct(new BookingResource(new Booking), new Booking());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(array $data)
    {
        // Create the model - defaults will be applied from model $attributes
        $model = $this->model::create($data);

        return $this->resource::make($model->fresh()->load(['preparedByUser.getUserMetas']));
    }

    /**
     * Get Details for editing the specified resource.
     */
    public function show(int $id)
    {
        $model = $this->model::with(['shippingLine', 'cypaFrom', 'cypaTo', 'containers', 'preparedByUser.getUserMetas'])
            ->withCount(['activeBookingContainers as containers_count'])
            ->findOrFail($id);

        // Count waybills for this booking
        $actualNoOfWaybill = \Illuminate\Support\Facades\DB::table('waybill_details')
            ->where('booking_id', $id)
            ->whereNull('deleted_at')
            ->count();

        // Add the count to the model as an attribute
        $model->actual_no_of_waybill = $actualNoOfWaybill;

        return $this->resource::make($model);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(array $data, $id)
    {
        $model = $this->model::findOrFail($id);
        $model->update($data);
        return $this->resource::make(
            $model->fresh()
                ->load(['shippingLine', 'cypaFrom', 'cypaTo', 'containers', 'preparedByUser.getUserMetas'])
                ->loadCount(['activeBookingContainers as containers_count'])
        );
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        try {
            $allBookings = $this->getTotalCount();
            $trashedBookings = $this->getTrashedCount();

            $query = Booking::query()
                ->with(['shippingLine', 'cypaFrom', 'cypaTo', 'preparedByUser.getUserMetas'])
                ->withCount(['activeBookingContainers as containers_count']);

            // Apply onlyTrashed() first if we're in trash view
            if ($trash) {
                $query->onlyTrashed();
            }

            // Then apply search conditions
            if (request('search')) {
                $query->where(function ($q) {
                    $q->where('reference_number', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('vessel', 'LIKE', '%' . request('search') . '%')
                        ->orWhereHas('shippingLine', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        })
                        ->orWhereHas('cypaFrom', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        })
                        ->orWhereHas('cypaTo', function ($q) {
                            $q->where('name', 'LIKE', '%' . request('search') . '%');
                        });
                });
            }

            // Filter by vessel
            if (request('vessel')) {
                $query->where('vessel', 'LIKE', '%' . request('vessel') . '%');
            }

            // Filter by shipping_line_id
            if (request('shipping_line_id')) {
                $query->where('shipping_line_id', request('shipping_line_id'));
            }

            // Filter by cypa_id_from
            if (request('cypa_id_from')) {
                $query->where('cypa_id_from', request('cypa_id_from'));
            }

            // Filter by cypa_id_to
            if (request('cypa_id_to')) {
                $query->where('cypa_id_to', request('cypa_id_to'));
            }

            // Filter by is_complete
            if (request()->has('is_complete')) {
                $query->where('is_complete', request('is_complete'));
            }

            // Filter by is_ship_in
            if (request()->has('is_ship_in')) {
                $query->where('is_ship_in', request('is_ship_in'));
            }

            // Filter by expected_date
            if (request('expected_date')) {
                $query->whereDate('expected_date', request('expected_date'));
            }

            // Apply ordering
            if (request('order')) {
                $query->orderBy(request('order'), request('sort') ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return BookingResource::collection(
                $query->paginate($perPage)->withQueryString()
            )->additional(['meta' => ['all' => $allBookings, 'trashed' => $trashedBookings]]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch bookings: ' . $e->getMessage());
        }
    }

    /**
     * Get bookings by shipping line ID, optionally filtered by expected_date range.
     * Includes total_cost, remaining_balance, total_paid based on waybills (total_rate_per_client) in the filtered set.
     */
    public function listByShippingLine(int $shippingLineId, ?string $expectedDateFrom = null, ?string $expectedDateTo = null, int $perPage = 10, ?int $isComplete = null)
    {
        $baseQuery = Booking::query()
            ->where('shipping_line_id', $shippingLineId);

        if ($expectedDateFrom) {
            $baseQuery->whereDate('expected_date', '>=', $expectedDateFrom);
        }
        if ($expectedDateTo) {
            $baseQuery->whereDate('expected_date', '<=', $expectedDateTo);
        }
        if (!is_null($isComplete)) {
            $baseQuery->where('is_complete', (int) $isComplete);
        }

        $filteredBookingIds = (clone $baseQuery)->pluck('id');

        $totalCost = (float) WaybillDetail::whereIn('booking_id', $filteredBookingIds)->sum('total_rate_per_client');
        $remainingBalance = (float) WaybillDetail::whereIn('booking_id', (clone $baseQuery)->where('is_complete', false)->pluck('id'))->sum('total_rate_per_client');
        $totalPaid = (float) WaybillDetail::whereIn('booking_id', (clone $baseQuery)->where('is_complete', true)->pluck('id'))->sum('total_rate_per_client');

        $query = (clone $baseQuery)
            ->with(['shippingLine', 'cypaFrom', 'cypaTo', 'preparedByUser.getUserMetas'])
            ->withCount(['activeBookingContainers as containers_count'])
            ->orderBy('expected_date', 'asc')
            ->orderBy('id', 'desc');

        $paginator = $query->paginate($perPage)->withQueryString();

        $this->attachSoaTagsToBookings($paginator->getCollection(), $shippingLineId);

        return BookingResource::collection($paginator)->additional([
            'total_cost' => round($totalCost, 2),
            'remaining_balance' => round($remainingBalance, 2),
            'total_paid' => round($totalPaid, 2),
        ]);
    }

    /**
     * Attach SOA badge/tag fields for bookings on the current page only.
     *
     * Avoids FE scanning every paginated SOA page. One query scoped to page booking IDs.
     *
     * @param  Collection<int, Booking>  $bookings
     */
    private function attachSoaTagsToBookings(Collection $bookings, int $shippingLineId): void
    {
        if ($bookings->isEmpty()) {
            return;
        }

        $bookingIds = $bookings->pluck('id')->map(fn ($id) => (int) $id)->all();
        $soaByBookingId = $this->buildSoaTagMapForBookingIds($bookingIds, $shippingLineId);

        foreach ($bookings as $booking) {
            $tag = $soaByBookingId[(int) $booking->id] ?? null;

            $booking->setAttribute('has_soa', $tag !== null);
            $booking->setAttribute('soa_id', $tag['soa_id'] ?? null);
            $booking->setAttribute('soa_dli_sa_number', $tag['soa_dli_sa_number'] ?? null);
        }
    }

    /**
     * Map booking_id => SOA tag for IDs that appear in active SOAs of the shipping line.
     * If a booking appears in more than one SOA, the newest SOA (highest id) wins.
     *
     * @param  array<int, int>  $bookingIds
     * @return array<int, array{soa_id: int, soa_dli_sa_number: string|null}>
     */
    private function buildSoaTagMapForBookingIds(array $bookingIds, int $shippingLineId): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $soas = StatementOfAccount::query()
            ->select(['id', 'dli_sa_number', 'booking_ids'])
            ->where('shipping_line_id', $shippingLineId)
            ->where(function ($query) use ($bookingIds) {
                foreach ($bookingIds as $bookingId) {
                    $query->orWhereRaw(
                        "JSON_CONTAINS(booking_ids, ?, '$')",
                        [json_encode((int) $bookingId)]
                    );
                }
            })
            ->orderByDesc('id')
            ->get();

        $map = [];

        foreach ($soas as $soa) {
            foreach ($soa->booking_ids ?? [] as $bookingId) {
                $bookingId = (int) $bookingId;

                if ($bookingId <= 0 || !in_array($bookingId, $bookingIds, true)) {
                    continue;
                }

                // First match wins because SOAs are ordered by id DESC (newest first).
                if (isset($map[$bookingId])) {
                    continue;
                }

                $map[$bookingId] = [
                    'soa_id' => (int) $soa->id,
                    'soa_dli_sa_number' => $soa->dli_sa_number,
                ];
            }
        }

        return $map;
    }

    /**
     * Get remaining container breakdown for a booking.
     */
    public function remainingContainer(int $id): array
    {
        $booking = Booking::query()
            ->withCount(['activeBookingContainers as containers_count'])
            ->findOrFail($id);

        $expectedContainer = (int) ($booking->expected_container ?? 0);
        $containersCount = (int) ($booking->containers_count ?? 0);

        return [
            'booking_id' => (int) $booking->id,
            'expected_container' => $expectedContainer,
            'containers_count' => $containersCount,
            'remaining_container' => $expectedContainer - $containersCount,
        ];
    }
}
