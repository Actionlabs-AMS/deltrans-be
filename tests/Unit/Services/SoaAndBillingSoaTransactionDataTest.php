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
}
