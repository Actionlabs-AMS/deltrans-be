<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Container;

class ContainerObserver
{
    private const WEEKS_UNTIL_AUTO_COMPLETE = 3;

    public function saved(Container $container): void
    {
        $this->extendBookingTimer((int) $container->booking_id);
    }

    public function deleted(Container $container): void
    {
        $this->extendBookingTimer((int) $container->booking_id);
    }

    private function extendBookingTimer(int $bookingId): void
    {
        Booking::query()
            ->where('id', $bookingId)
            ->where('is_complete', false)
            ->whereNotNull('auto_complete_at')
            ->update(['auto_complete_at' => now()->addWeeks(self::WEEKS_UNTIL_AUTO_COMPLETE)]);
    }
}

