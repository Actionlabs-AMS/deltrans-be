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
     *
     * @param array<int> $bookingIds
     * @param int $type 1 = SOA, 2 = Billing, 3 = Invoice
     * @return array{valid_bookings: array<int, array>, invalid_bookings: array<int, array{booking: array, reason: string}>}
     */
    public function validateBookings(array $bookingIds, int $type): array
    {
        $bookingIds = array_values(array_unique(array_map('intval', $bookingIds)));
        if (empty($bookingIds)) {
            return [
                'valid_bookings' => [],
                'invalid_bookings' => [],
            ];
        }

        $hasSoa = $this->getBookingIdsWithSoa($bookingIds);
        $hasBilling = $type >= 2 ? $this->getBookingIdsWithBilling($bookingIds) : [];
        $hasInvoice = $type === 3 ? $this->getBookingIdsWithInvoice($bookingIds) : [];

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

            if ($type === 2) {
                if (!$idHasSoa) {
                    $invalidReasons[$id] = 'Booking has no existing SOA.';
                } elseif ($idHasBilling) {
                    $invalidReasons[$id] = 'Booking already has an existing Billing.';
                } else {
                    $validIds[] = $id;
                }
                continue;
            }

            if ($type === 3) {
                if (!$idHasSoa) {
                    $invalidReasons[$id] = 'Booking has no existing SOA.';
                } elseif ($idHasInvoice) {
                    $invalidReasons[$id] = 'Booking already has an existing Invoice.';
                } else {
                    $validIds[] = $id;
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

        return [
            'valid_bookings' => $validBookingsArray,
            'invalid_bookings' => $invalidBookings,
        ];
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
