<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\WaybillDetail;
use App\Services\SoaAndBillingService;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Exercises SoaAndBillingService::buildSoaTransactionData (SOA PDF / line-items totals).
 * Amounts must come from waybill_details only (no rate_per_clients lookup).
 */
class SoaAndBillingSoaTransactionDataTest extends TestCase
{
    private function invokeBuildSoaTransactionData(Collection $waybills, object $soa, Collection $transactionColumns): array
    {
        $service = app(SoaAndBillingService::class);
        $method = new ReflectionMethod(SoaAndBillingService::class, 'buildSoaTransactionData');
        $method->setAccessible(true);

        return $method->invoke($service, $waybills, $soa, $transactionColumns);
    }

    public function test_totals_use_rate_from_waybill_when_vat_applies(): void
    {
        $waybill = new WaybillDetail([
            'rate' => 5000,
            'total_rate_per_client' => 0,
            'has_vat' => true,
        ]);
        $waybill->id = 1;

        $result = $this->invokeBuildSoaTransactionData(
            collect([$waybill]),
            (object) ['work_order' => '-'],
            collect()
        );

        $this->assertEqualsWithDelta(5000.0, (float) $result['totalAmount'], 0.001);
        $this->assertEqualsWithDelta(600.0, (float) $result['totalVat'], 0.001);
        $this->assertEqualsWithDelta(5600.0, (float) $result['grandTotal'], 0.001);
    }

    public function test_totals_use_total_rate_per_client_when_rate_is_unset(): void
    {
        // Do not set rate: assigning null can be cast to 0 on the model, which would skip total_rate_per_client (?? only replaces null).
        $waybill = new WaybillDetail([
            'total_rate_per_client' => 3000,
            'has_vat' => false,
        ]);
        $waybill->id = 1;

        $result = $this->invokeBuildSoaTransactionData(
            collect([$waybill]),
            (object) ['work_order' => '-'],
            collect()
        );

        $this->assertEqualsWithDelta(3000.0, (float) $result['totalAmount'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $result['totalVat'], 0.001);
        $this->assertEqualsWithDelta(3000.0, (float) $result['grandTotal'], 0.001);
    }

    public function test_zero_amount_stays_zero_even_with_booking_loaded(): void
    {
        $booking = new Booking();
        $booking->cypa_id_from = 1;
        $booking->setRelation('containers', collect());

        $waybill = new WaybillDetail([
            'shipping_line_id' => 1,
            'container_size' => '20ft',
            'rate' => 0,
            'total_rate_per_client' => 0,
            'has_vat' => true,
        ]);
        $waybill->id = 1;
        $waybill->setRelation('booking', $booking);

        $result = $this->invokeBuildSoaTransactionData(
            collect([$waybill]),
            (object) ['work_order' => '-'],
            collect()
        );

        $this->assertEqualsWithDelta(0.0, (float) $result['totalAmount'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $result['totalVat'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $result['grandTotal'], 0.001);
    }

    public function test_multiplies_amount_by_container_count_from_booking(): void
    {
        $booking = new Booking();
        $booking->setRelation('containers', collect([
            (object) ['waybill_id' => 1, 'container_number' => 'A'],
            (object) ['waybill_id' => 1, 'container_number' => 'B'],
        ]));

        $waybill = new WaybillDetail([
            'rate' => 1000,
            'total_rate_per_client' => 0,
            'has_vat' => false,
        ]);
        $waybill->id = 1;
        $waybill->setRelation('booking', $booking);

        $result = $this->invokeBuildSoaTransactionData(
            collect([$waybill]),
            (object) ['work_order' => '-'],
            collect()
        );

        $this->assertEqualsWithDelta(2000.0, (float) $result['totalAmount'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $result['totalVat'], 0.001);
        $this->assertEqualsWithDelta(2000.0, (float) $result['grandTotal'], 0.001);
    }

    private function invokeEnsureBookingNumberColumnFirst(Collection $transactionColumns): Collection
    {
        $service = app(SoaAndBillingService::class);
        $method = new ReflectionMethod(SoaAndBillingService::class, 'ensureBookingNumberColumnFirst');
        $method->setAccessible(true);

        return $method->invoke($service, $transactionColumns);
    }

    public function test_ensure_booking_number_column_is_first_when_missing(): void
    {
        $columns = collect([
            (object) ['id' => 2, 'name' => 'Date', 'description' => 'Date'],
            (object) ['id' => 3, 'name' => 'Amount', 'description' => 'Amount'],
        ]);

        $result = $this->invokeEnsureBookingNumberColumnFirst($columns);

        $this->assertGreaterThanOrEqual(3, $result->count());
        $firstName = strtolower(trim((string) $result->first()->name));
        $this->assertContains($firstName, ['booking number', 'booking no']);
        $this->assertSame('Date', $result->get(1)->name);
        $this->assertSame('Amount', $result->get(2)->name);
        $bookingCount = $result->filter(function ($col) {
            return in_array(strtolower(trim((string) ($col->name ?? ''))), ['booking number', 'booking no'], true);
        })->count();
        $this->assertSame(1, $bookingCount);
    }

    public function test_ensure_booking_number_column_is_not_duplicated(): void
    {
        $bookingCol = (object) ['id' => 9, 'name' => 'Booking Number', 'description' => 'Booking number'];
        $columns = collect([
            (object) ['id' => 2, 'name' => 'Date', 'description' => 'Date'],
            $bookingCol,
            (object) ['id' => 3, 'name' => 'Amount', 'description' => 'Amount'],
        ]);

        $result = $this->invokeEnsureBookingNumberColumnFirst($columns);

        $this->assertCount(3, $result);
        $this->assertSame('Booking Number', $result->first()->name);
        $this->assertSame(9, $result->first()->id);
        $this->assertSame('Date', $result->get(1)->name);
        $this->assertSame('Amount', $result->get(2)->name);
    }

    public function test_booking_number_maps_reference_number(): void
    {
        $booking = new Booking();
        $booking->reference_number = 'REF-999';
        $booking->setRelation('containers', collect());

        $waybill = new WaybillDetail([
            'rate' => 0,
            'total_rate_per_client' => 0,
            'has_vat' => false,
        ]);
        $waybill->id = 1;
        $waybill->setRelation('booking', $booking);

        $result = $this->invokeBuildSoaTransactionData(
            collect([$waybill]),
            (object) ['work_order' => '-'],
            collect([(object) ['id' => 1, 'name' => 'Booking Number']])
        );

        $this->assertCount(1, $result['transactionData']);
        $this->assertSame('REF-999', $result['transactionData'][0]['Booking Number']);
    }
}
