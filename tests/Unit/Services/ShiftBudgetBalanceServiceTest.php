<?php

namespace Tests\Unit\Services;

use App\Models\FundsForStackRun;
use App\Models\IssuedBudget;
use App\Models\ShiftBudgetBalance;
use App\Services\ShiftBudgetBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftBudgetBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShiftBudgetBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftBudgetBalanceService::class);
    }

    public function test_computes_remaining_coh_with_carryover_between_shifts(): void
    {
        IssuedBudget::query()->create([
            'transaction_date' => '2026-05-27',
            'shift' => 'Night',
            'amount' => 10000,
            'source' => 'Test',
        ]);
        FundsForStackRun::query()->create([
            'transaction_date' => '2026-05-27',
            'shift' => 'Night',
            'amount' => 8000,
            'remarks' => 'Night expense',
        ]);

        $this->service->recalculateFrom('2026-05-27', 'Night');

        $night = ShiftBudgetBalance::query()
            ->whereDate('transaction_date', '2026-05-27')
            ->where('shift', 'Night')
            ->first();

        $this->assertNotNull($night);
        $this->assertEquals(10000.0, (float) $night->issued_budget);
        $this->assertEquals(0.0, (float) $night->carried_from_previous);
        $this->assertEquals(10000.0, (float) $night->total_budget);
        $this->assertEquals(8000.0, (float) $night->total_expense);
        $this->assertEquals(2000.0, (float) $night->remaining_coh);

        IssuedBudget::query()->create([
            'transaction_date' => '2026-05-28',
            'shift' => 'Day',
            'amount' => 15000,
            'source' => 'Test',
        ]);
        FundsForStackRun::query()->create([
            'transaction_date' => '2026-05-28',
            'shift' => 'Day',
            'amount' => 12500,
            'remarks' => 'Day expense',
        ]);

        $this->service->recalculateFrom('2026-05-28', 'Day');

        $day = ShiftBudgetBalance::query()
            ->whereDate('transaction_date', '2026-05-28')
            ->where('shift', 'Day')
            ->first();

        $this->assertNotNull($day);
        $this->assertEquals(15000.0, (float) $day->issued_budget);
        $this->assertEquals(2000.0, (float) $day->carried_from_previous);
        $this->assertEquals(17000.0, (float) $day->total_budget);
        $this->assertEquals(12500.0, (float) $day->total_expense);
        $this->assertEquals(4500.0, (float) $day->remaining_coh);
        $this->assertSame('2026-05-27', $day->previous_shift_date?->format('Y-m-d'));
        $this->assertSame('Night', $day->previous_shift);
    }
}
