<?php

namespace App\Services;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BillingStatement;
use App\Models\Invoice;
use App\Models\StatementOfAccount;
use App\Models\WaybillDetail;
use Illuminate\Support\Facades\DB;

class SoaBillingCheckerService
{
    /**
     * Validate bookings for SOA (type 1), Billing (type 2), or Invoice (type 3).
     * Returns valid_bookings and invalid_bookings (with reason per invalid).
     * For type 2 and 3, also returns has_soa (true if any selected booking has an SOA, default false).
     *
     * Accepts booking_ids, statement_of_account_id (single), or statement_of_account_ids (multi).
     *
     * @param array<int> $bookingIds
     * @param int $type 1 = SOA, 2 = Billing, 3 = Invoice
     * @return array{valid_bookings: array, invalid_bookings: array, has_soa?: bool}
     */
    public function validateBookings(array $bookingIds, int $type): array
    {
        $bookingIds = array_values(array_unique(array_map('intval', $bookingIds)));

        $statementOfAccountIds = $this->resolveStatementOfAccountIdsFromRequest();
        $usingSoaIds = !empty($statementOfAccountIds);

        if (empty($bookingIds) && $usingSoaIds) {
            $soas = StatementOfAccount::select('id', 'booking_ids', 'shipping_line_id')
                ->whereIn('id', $statementOfAccountIds)
                ->get();
            $bookingIds = $soas
                ->flatMap(fn ($soa) => $soa->booking_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if (empty($bookingIds)) {
            return [
                'valid_bookings' => [],
                'invalid_bookings' => [],
                'has_soa' => false,
            ];
        }

        // Multi-SOA invoice/billing batch: reject entire set if SOAs span shipping lines
        // or any SOA is already linked to an invoice (type 3) / billing (type 2).
        if ($usingSoaIds && ($type === 2 || $type === 3)) {
            $batchError = $this->validateSoaBatchRules($statementOfAccountIds, $type);
            if ($batchError !== null) {
                return $this->allBookingsInvalid($bookingIds, $batchError, true);
            }
        }

        $hasSoa = $usingSoaIds ? $bookingIds : $this->getBookingIdsWithSoa($bookingIds);
        $hasBilling = [];
        $hasInvoice = [];
        if ($type >= 2) {
            if ($usingSoaIds) {
                $soaIdsWithBilling = BillingStatement::whereIn('statement_of_account_id', $statementOfAccountIds)
                    ->pluck('statement_of_account_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->all();
                $hasBilling = !empty($soaIdsWithBilling) ? $bookingIds : [];
            } else {
                $hasBilling = $this->getBookingIdsWithBilling($bookingIds);
            }
        }
        if ($type === 3) {
            if ($usingSoaIds) {
                $soaIdsWithInvoice = $this->getSoaIdsAlreadyInvoiced($statementOfAccountIds);
                $hasInvoice = !empty($soaIdsWithInvoice) ? $bookingIds : [];
            } else {
                $hasInvoice = $this->getBookingIdsWithInvoice($bookingIds);
            }
        }

        // For type 2 and 3: true if any selected booking has an SOA, default false.
        // When SOA id(s) are provided (and booking_ids is not), assume bookings belong to those SOAs.
        $has_soa = ($type === 2 || $type === 3) && !empty($hasSoa);

        // Condition: booking is valid only if it has at least one waybill (waybill_details with this booking_id).
        $bookingIdsWithWaybills = WaybillDetail::whereIn('booking_id', $bookingIds)
            ->distinct()
            ->pluck('booking_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $validIds = [];
        $invalidReasons = []; // booking_id => reason

        foreach ($bookingIds as $id) {
            if (!in_array($id, $bookingIdsWithWaybills, true)) {
                $invalidReasons[$id] = 'Booking must have at least one waybill.';
                continue;
            }

            $idHasSoa = in_array($id, $hasSoa, true);
            $idHasBilling = in_array($id, $hasBilling, true);
            $idHasInvoice = in_array($id, $hasInvoice, true);

            if ($type === 1) {
                if ($idHasSoa) {
                    $invalidReasons[$id] = 'Booking already has an existing SOA.';
                } else {
                    $validIds[] = $id;
                }
                continue;
            }

            // Type 2 (Billing): no SOA + no billing = valid; has SOA + no billing = valid; has billing = invalid.
            if ($type === 2) {
                if ($idHasBilling) {
                    $invalidReasons[$id] = 'Booking already has an existing Billing.';
                } else {
                    $validIds[] = $id;
                }
                continue;
            }

            // Type 3 (Invoice): no SOA + no invoice = valid; has SOA + no invoice = valid; has invoice = invalid.
            if ($type === 3) {
                if ($idHasInvoice) {
                    $invalidReasons[$id] = 'Booking already has an existing Invoice.';
                } else {
                    $validIds[] = $id;
                }
            }
        }

        // Type 2 or 3 (when has_soa and selecting by booking_ids only):
        // When has_soa is true: all must have SOA and belong to the same single SOA.
        // Multi-SOA selection is allowed only via statement_of_account_ids.
        if (($type === 2 || $type === 3) && $has_soa && !$usingSoaIds) {
            $idsWithoutSoa = array_diff($bookingIds, $hasSoa);
            if (!empty($idsWithoutSoa)) {
                foreach ($bookingIds as $id) {
                    $invalidReasons[$id] = 'All selected bookings must have an SOA. One or more bookings have no existing SOA.';
                }
                $validIds = [];
            } else {
                $distinctSoaIds = $this->getDistinctSoaIdsForBookings($bookingIds);
                if (count($distinctSoaIds) > 1) {
                    foreach ($bookingIds as $id) {
                        $invalidReasons[$id] = 'Selected bookings must belong to a single SOA. Bookings span multiple SOAs.';
                    }
                    $validIds = [];
                }
            }
        }

        $bookings = Booking::whereIn('id', $bookingIds)->with('shippingLine')->get()->keyBy('id');

        $validBookings = $bookings->whereIn('id', $validIds)->values();
        $invalidBookings = [];
        foreach (array_intersect($bookingIds, array_keys($invalidReasons)) as $id) {
            $booking = $bookings->get($id);
            if ($booking) {
                $invalidBookings[] = [
                    'booking' => (new BookingResource($booking))->toArray(request()),
                    'reason' => $invalidReasons[$id],
                ];
            }
        }

        $validBookingsArray = $validBookings->map(fn ($b) => (new BookingResource($b))->toArray(request()))->values()->all();

        $result = [
            'valid_bookings' => $validBookingsArray,
            'invalid_bookings' => $invalidBookings,
        ];
        if ($type === 2 || $type === 3) {
            $result['has_soa'] = $has_soa;
        }
        return $result;
    }

    /**
     * Resolve SOA IDs from request: prefer statement_of_account_ids, else statement_of_account_id.
     *
     * @return array<int>
     */
    private function resolveStatementOfAccountIdsFromRequest(): array
    {
        $ids = request()->input('statement_of_account_ids');
        if (is_array($ids) && count($ids) > 0) {
            return array_values(array_unique(array_map('intval', $ids)));
        }

        if (request()->filled('statement_of_account_id')) {
            return [(int) request()->input('statement_of_account_id')];
        }

        return [];
    }

    /**
     * Batch rules when validating via SOA id(s): same shipping line; none already invoiced (type 3).
     *
     * @param array<int> $soaIds
     */
    private function validateSoaBatchRules(array $soaIds, int $type): ?string
    {
        $soas = StatementOfAccount::whereIn('id', $soaIds)->get(['id', 'shipping_line_id']);
        if ($soas->count() !== count($soaIds)) {
            return 'One or more selected statements of account do not exist.';
        }

        $shippingLineIds = $soas->pluck('shipping_line_id')->unique()->filter()->values();
        if ($shippingLineIds->count() > 1) {
            return 'All selected statements of account must belong to the same shipping line.';
        }

        if ($type === 3) {
            $alreadyInvoiced = $this->getSoaIdsAlreadyInvoiced($soaIds);
            if (!empty($alreadyInvoiced)) {
                return 'One or more selected statements of account are already linked to an invoice: '
                    . implode(', ', $alreadyInvoiced) . '.';
            }
        }

        return null;
    }

    /**
     * @param array<int> $bookingIds
     * @return array{valid_bookings: array, invalid_bookings: array, has_soa: bool}
     */
    private function allBookingsInvalid(array $bookingIds, string $reason, bool $hasSoa): array
    {
        $bookings = Booking::whereIn('id', $bookingIds)->with('shippingLine')->get();
        $invalidBookings = $bookings->map(fn ($booking) => [
            'booking' => (new BookingResource($booking))->toArray(request()),
            'reason' => $reason,
        ])->values()->all();

        return [
            'valid_bookings' => [],
            'invalid_bookings' => $invalidBookings,
            'has_soa' => $hasSoa,
        ];
    }

    /**
     * @param array<int> $soaIds
     * @return array<int>
     */
    private function getSoaIdsAlreadyInvoiced(array $soaIds): array
    {
        if (empty($soaIds)) {
            return [];
        }

        return DB::table('invoice_statement_of_account')
            ->whereIn('statement_of_account_id', $soaIds)
            ->pluck('statement_of_account_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Distinct SOA IDs that contain at least one of the given booking IDs.
     * Used to enforce "selected bookings must be in a single SOA" for Billing/Invoice.
     *
     * @param array<int> $bookingIds
     * @return array<int>
     */
    private function getDistinctSoaIdsForBookings(array $bookingIds): array
    {
        if (empty($bookingIds)) {
            return [];
        }
        $soas = StatementOfAccount::select('id', 'booking_ids')->get();
        $distinctSoaIds = [];
        foreach ($soas as $soa) {
            $ids = $soa->booking_ids ?? [];
            foreach ($ids as $bid) {
                $bid = (int) $bid;
                if (in_array($bid, $bookingIds, true)) {
                    $distinctSoaIds[$soa->id] = true;
                    break;
                }
            }
        }
        return array_keys($distinctSoaIds);
    }

    /**
     * Booking IDs (from input) that appear in at least one SOA's booking_ids.
     *
     * @param array<int> $bookingIds
     * @return array<int>
     */
    private function getBookingIdsWithSoa(array $bookingIds): array
    {
        if (empty($bookingIds)) {
            return [];
        }
        $soas = StatementOfAccount::select('booking_ids')->get();
        $inAnySoa = [];
        foreach ($soas as $soa) {
            $ids = $soa->booking_ids ?? [];
            foreach ($ids as $bid) {
                $bid = (int) $bid;
                if (in_array($bid, $bookingIds, true)) {
                    $inAnySoa[$bid] = true;
                }
            }
        }
        return array_keys($inAnySoa);
    }

    /**
     * Booking IDs (from input) that are in an SOA which has at least one BillingStatement.
     *
     * @param array<int> $bookingIds
     * @return array<int>
     */
    private function getBookingIdsWithBilling(array $bookingIds): array
    {
        if (empty($bookingIds)) {
            return [];
        }
        $billings = BillingStatement::with('statementOfAccount:id,booking_ids')->get();
        $inAnyBilling = [];
        foreach ($billings as $b) {
            $soa = $b->statementOfAccount;
            if (!$soa) {
                continue;
            }
            $ids = $soa->booking_ids ?? [];
            foreach ($ids as $bid) {
                $bid = (int) $bid;
                if (in_array($bid, $bookingIds, true)) {
                    $inAnyBilling[$bid] = true;
                }
            }
        }
        return array_keys($inAnyBilling);
    }

    /**
     * Booking IDs (from input) that are in an SOA which has at least one Invoice.
     *
     * @param array<int> $bookingIds
     * @return array<int>
     */
    private function getBookingIdsWithInvoice(array $bookingIds): array
    {
        if (empty($bookingIds)) {
            return [];
        }

        $invoicedSoaIds = DB::table('invoice_statement_of_account')
            ->pluck('statement_of_account_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        if (empty($invoicedSoaIds)) {
            return [];
        }

        $soas = StatementOfAccount::select('id', 'booking_ids')
            ->whereIn('id', $invoicedSoaIds)
            ->get();

        $inAnyInvoice = [];
        foreach ($soas as $soa) {
            foreach ($soa->booking_ids ?? [] as $bid) {
                $bid = (int) $bid;
                if (in_array($bid, $bookingIds, true)) {
                    $inAnyInvoice[$bid] = true;
                }
            }
        }
        return array_keys($inAnyInvoice);
    }
}
