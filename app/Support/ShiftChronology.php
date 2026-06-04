<?php

namespace App\Support;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Carryover order: Day → Night on the same calendar day, then next calendar Day
 * (e.g. May 27 Night → May 28 Day). "1st" shares the Day slot; "2nd" shares the Night slot.
 */
class ShiftChronology
{
    public const SHIFTS = ['Day', 'Night', '1st', '2nd'];

    private const SLOTS_PER_DAY = 2;

    private const EPOCH = '1970-01-01';

    public static function isValidShift(string $shift): bool
    {
        return in_array($shift, self::SHIFTS, true);
    }

    public static function shiftIndex(string $shift): int
    {
        return match ($shift) {
            'Day', '1st' => 0,
            'Night', '2nd' => 1,
            default => throw new InvalidArgumentException("Invalid shift: {$shift}"),
        };
    }

    public static function toPosition(string $date, string $shift): int
    {
        $days = (int) Carbon::parse(self::EPOCH)->startOfDay()
            ->diffInDays(Carbon::parse($date)->startOfDay());

        return ($days * self::SLOTS_PER_DAY) + self::shiftIndex($shift);
    }

    /**
     * @return array{0: string, 1: string} [transaction_date, shift]
     */
    public static function fromPosition(int $position): array
    {
        $days = intdiv($position, self::SLOTS_PER_DAY);
        $slot = $position % self::SLOTS_PER_DAY;
        $shift = $slot === 0 ? 'Day' : 'Night';
        $date = Carbon::parse(self::EPOCH)->startOfDay()->addDays($days)->format('Y-m-d');

        return [$date, $shift];
    }

    /**
     * @return array{date: string, shift: string}|null
     */
    public static function previous(string $date, string $shift): ?array
    {
        $position = self::toPosition($date, $shift);
        if ($position <= 0) {
            return null;
        }

        [$prevDate, $prevShift] = self::fromPosition($position - 1);

        return ['date' => $prevDate, 'shift' => $prevShift];
    }

    /**
     * @return array{date: string, shift: string}
     */
    public static function next(string $date, string $shift): array
    {
        [$nextDate, $nextShift] = self::fromPosition(self::toPosition($date, $shift) + 1);

        return ['date' => $nextDate, 'shift' => $nextShift];
    }
}
