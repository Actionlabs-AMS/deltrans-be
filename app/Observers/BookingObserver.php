<?php

namespace App\Observers;

use App\Models\Booking;

class BookingObserver
{
    private const WEEKS_UNTIL_AUTO_COMPLETE = 3;

    public function updated(Booking $booking): void
    {
        if ((bool) $booking->is_complete) {
            return;
        }

        if ($booking->auto_complete_at === null) {
            // Timer not started yet (starts after SOA creation)
            return;
        }

        $changedKeys = array_keys($booking->getChanges());
        $ignored = ['auto_complete_at', 'is_complete', 'updated_at', 'created_at', 'deleted_at'];

        $meaningfulChanges = array_values(array_diff($changedKeys, $ignored));
        if (empty($meaningfulChanges)) {
            return;
        }

        $booking->updateQuietly([
            'auto_complete_at' => now()->addWeeks(self::WEEKS_UNTIL_AUTO_COMPLETE),
        ]);
    }
}

