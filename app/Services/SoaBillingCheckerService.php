<?php

namespace App\Services;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BillingStatement;
use App\Models\Invoice;
use App\Models\StatementOfAccount;

class SoaBillingCheckerService
{
    /**
     * Validate bookings for SOA (type 1), Billing (type 2), or Invoice (type 3).
     * Returns valid_bookings and invalid_bookings (with reason per invalid).
     * For type 2 and 3, also returns has_soa (true if any selected booking has an SOA, default false).
     *
     * @param array<int> $bookingIds
     * @param int $type 1 = SOA, 2 = Billing, 3 = Invoice
     * @return array{valid_bookings: array, invalid_bookings: array, has_soa?: bool}
     */
    public function validateBookings(array $bookingIds, int $type): array
    {
        $bookingIds = array_values(array_unique(array_map('intval', $bookingIds)));
        if (empty($bookingIds)) {
            return [
                'valid_bookings' => [],
                'invalid_bookings' => [],
                'has_soa' => false,
            ];
        }

        $hasSoa = $this->getBookingIdsWithSoa($bookingIds);
        $hasBilling = $type >= 2 ? $this->getBookingIdsWithBilling($bookingIds) : [];
        $hasInvoice = $type === 3 ? $this->getBookingIdsWithInvoice($bookingIds) : [];

        // For type 2 and 3: true if any selected booking has an SOA, default false.
        $has_soa = ($type === 2 || $type === 3) && !empty($hasSoa);

        $validIds = [];
        $invalidReasons = []; // booking_id => reason

        foreach ($bookingIds as $id) {
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

        // Type 2 or 3 (when has_soa): 1 Billing = 1 SOA, 1 Invoice = 1 SOA.
        // When has_soa is true: all must have SOA and belong to the same single SOA.
        // When has_soa is false (all bookings have no SOA), valid from per-booking logic.
        if (($type === 2 || $type === 3) && $has_soa) {
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
        $invoices = Invoice::with('statementOfAccount:id,booking_ids')->get();
        $inAnyInvoice = [];
        foreach ($invoices as $inv) {
            $soa = $inv->statementOfAccount;
            if (!$soa) {
                continue;
            }
            $ids = $soa->booking_ids ?? [];
            foreach ($ids as $bid) {
                $bid = (int) $bid;
                if (in_array($bid, $bookingIds, true)) {
                    $inAnyInvoice[$bid] = true;
                }
            }
        }
        return array_keys($inAnyInvoice);
    }
}
