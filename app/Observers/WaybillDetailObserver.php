<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\WaybillDetail;

class WaybillDetailObserver
{
    private const WEEKS_UNTIL_AUTO_COMPLETE = 3;

    public function saved(WaybillDetail $waybill): void
    {
        $this->extendBookingTimer((int) $waybill->booking_id);
    }

    public function deleted(WaybillDetail $waybill): void
    {
        $this->extendBookingTimer((int) $waybill->booking_id);
    }

    public function restored(WaybillDetail $waybill): void
    {
        $this->extendBookingTimer((int) $waybill->booking_id);
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

