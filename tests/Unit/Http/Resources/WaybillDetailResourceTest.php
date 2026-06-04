<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\WaybillDetailResource;
use App\Models\Booking;
use App\Models\Container;
use App\Models\ContainerYard;
use App\Models\Driver;
use App\Models\FixedExpense;
use App\Models\FleetTruck;
use App\Models\ShippingLine;
use App\Models\WaybillDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaybillDetailResourceTest extends TestCase
{
    use RefreshDatabase;

    private function createWaybill(): WaybillDetail
    {
        $shipping = ShippingLine::query()->create([
            'name' => 'Test Line',
            'email_address' => 'line@test.local',
        ]);

        $cypaFrom = ContainerYard::query()->create([
            'name' => 'CY From',
            'short_name' => 'CF',
            'location_type' => 'Container Yard',
            'is_active' => 1,
        ]);

        $cypaTo = ContainerYard::query()->create([
            'name' => 'CY To',
            'short_name' => 'CT',
            'location_type' => 'Container Yard',
            'is_active' => 1,
        ]);

        $truck = FleetTruck::query()->create([
            'plate_number' => 'TST-1000',
            'condition' => 'good',
            'is_active' => 1,
        ]);

        $driver = Driver::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'contact_number' => '000',
            'is_active' => 1,
        ]);

        $fixed = FixedExpense::query()->create([
            'shipping_line_id' => $shipping->id,
            'cypa_id_from' => $cypaFrom->id,
            'cypa_id_to' => $cypaTo->id,
            'container_size' => '20',
        ]);

        $booking = Booking::query()->create([
            'reference_number' => 'REF-TST-' . uniqid(),
            'vessel' => 'Vessel',
            'shipping_line_id' => $shipping->id,
            'cypa_id_from' => $cypaFrom->id,
            'cypa_id_to' => $cypaTo->id,
            'expected_date' => now()->toDateString(),
            'expected_container' => 10,
        ]);

        return WaybillDetail::query()->create([
            'waybill_number' => 'WB-TST-' . uniqid(),
            'transaction_date' => now()->toDateString(),
            'shipping_line_id' => $shipping->id,
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
            'container_size' => '20',
            'truck_plate_number' => $truck->plate_number,
            'pickup_date' => now()->toDateString(),
            'delivered_date' => now()->toDateString(),
            'no_of_days' => 1,
            'stack_run' => 0,
            'rate' => 100,
            'fixed_expense_id' => $fixed->id,
        ]);
    }

    public function test_includes_container_numbers_when_containers_are_loaded(): void
    {
        $waybill = $this->createWaybill();
        Container::query()->create([
            'container_number' => 'MSKU1111111',
            'booking_id' => $waybill->booking_id,
            'waybill_id' => $waybill->id,
        ]);
        Container::query()->create([
            'container_number' => 'MSKU2222222',
            'booking_id' => $waybill->booking_id,
            'waybill_id' => $waybill->id,
        ]);

        $payload = (new WaybillDetailResource($waybill->fresh()->load('containers')))->resolve();

        $this->assertSame(['MSKU1111111', 'MSKU2222222'], $payload['container_numbers']);
        $this->assertCount(2, $payload['containers']);
        $this->assertSame('MSKU1111111', $payload['containers'][0]['container_number']);
    }

    public function test_container_numbers_default_to_empty_when_relation_not_loaded(): void
    {
        $waybill = $this->createWaybill();

        $payload = (new WaybillDetailResource($waybill))->resolve();

        $this->assertSame([], $payload['container_numbers']);
        $this->assertArrayNotHasKey('containers', $payload);
    }
}
