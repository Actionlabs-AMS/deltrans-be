<?php

namespace Tests\Unit\Support;

use App\Support\ShiftChronology;
use PHPUnit\Framework\TestCase;

class ShiftChronologyTest extends TestCase
{
    public function test_night_previous_is_same_day_day(): void
    {
        $previous = ShiftChronology::previous('2026-05-27', 'Night');

        $this->assertSame(['date' => '2026-05-27', 'shift' => 'Day'], $previous);
    }

    public function test_day_previous_is_previous_night(): void
    {
        $previous = ShiftChronology::previous('2026-05-28', 'Day');

        $this->assertSame(['date' => '2026-05-27', 'shift' => 'Night'], $previous);
    }

    public function test_night_next_is_next_day_day(): void
    {
        $next = ShiftChronology::next('2026-05-27', 'Night');

        $this->assertSame(['date' => '2026-05-28', 'shift' => 'Day'], $next);
    }
}
