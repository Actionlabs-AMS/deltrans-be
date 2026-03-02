<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class AutoCompleteBookings extends Command
{
    protected $signature = 'bookings:auto-complete';
    protected $description = 'Mark bookings complete when auto_complete_at has passed';

    public function handle(): int
    {
        $now = now();

        $affected = Booking::query()
            ->where('is_complete', false)
            ->whereNotNull('auto_complete_at')
            ->where('auto_complete_at', '<=', $now)
            ->update([
                'is_complete' => true,
                'auto_complete_at' => null,
            ]);

        $this->info("Auto-completed {$affected} booking(s).");

        return self::SUCCESS;
    }
}

