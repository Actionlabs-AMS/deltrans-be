<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\StatementOfAccount;

class StatementOfAccountObserver
{
    private const WEEKS_UNTIL_AUTO_COMPLETE = 3;

    public function created(StatementOfAccount $soa): void
    {
        $this->resetTimerForBookingIds($soa->booking_ids ?? []);
    }

    public function updated(StatementOfAccount $soa): void
    {
        $currentBookingIds = $this->normalizeIds($soa->booking_ids ?? []);
        $originalBookingIds = $this->normalizeIds($this->decodeOriginalBookingIds($soa));

        $removedBookingIds = array_values(array_diff($originalBookingIds, $currentBookingIds));

        // Any SOA edit pushes the due date forward for current bookings
        $this->resetTimerForBookingIds($currentBookingIds);

        // If bookings were removed from this SOA, null the timer only if no other active SOA references them
        if (!empty($removedBookingIds)) {
            foreach ($removedBookingIds as $bid) {
                if (!$this->isBookingReferencedByAnyActiveSoa($bid, $soa->id)) {
                    Booking::query()
                        ->where('id', $bid)
                        ->where('is_complete', false)
                        ->update(['auto_complete_at' => null]);
                }
            }
        }
    }

    public function deleted(StatementOfAccount $soa): void
    {
        $bookingIds = $this->normalizeIds($soa->booking_ids ?? []);
        if (empty($bookingIds)) {
            return;
        }

        foreach ($bookingIds as $bid) {
            if (!$this->isBookingReferencedByAnyActiveSoa($bid, $soa->id)) {
                Booking::query()
                    ->where('id', $bid)
                    ->where('is_complete', false)
                    ->update(['auto_complete_at' => null]);
            }
        }
    }

    private function resetTimerForBookingIds(array $bookingIds): void
    {
        $ids = $this->normalizeIds($bookingIds);
        if (empty($ids)) {
            return;
        }

        $due = now()->addWeeks(self::WEEKS_UNTIL_AUTO_COMPLETE);

        Booking::query()
            ->whereIn('id', $ids)
            ->where('is_complete', false)
            ->update(['auto_complete_at' => $due]);
    }

    private function isBookingReferencedByAnyActiveSoa(int $bookingId, ?int $excludeSoaId = null): bool
    {
        $query = StatementOfAccount::query()
            ->whereNull('deleted_at')
            // MariaDB-safe: pass JSON literal (e.g. "6") instead of CAST(... AS JSON)
            ->whereRaw("JSON_CONTAINS(booking_ids, ?, '$')", [json_encode($bookingId)]);

        if ($excludeSoaId !== null) {
            $query->where('id', '!=', $excludeSoaId);
        }

        return $query->exists();
    }

    private function decodeOriginalBookingIds(StatementOfAccount $soa): array
    {
        $original = $soa->getOriginal('booking_ids');

        if (is_array($original)) {
            return $original;
        }

        if (is_string($original) && $original !== '') {
            $decoded = json_decode($original, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_map('intval', array_values($ids))));
    }
}

