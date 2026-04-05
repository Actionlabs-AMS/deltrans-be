<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Container;
use App\Models\ContainerYard;
use App\Models\Driver;
use App\Models\FixedExpense;
use App\Models\FleetTruck;
use App\Models\ShippingLine;
use App\Models\WaybillDetail;
use App\Services\BookingService;
use App\Services\WaybillDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRemainingContainersAfterWaybillTrashTest extends TestCase
{
    use RefreshDatabase;

    private function seedBookingGraph(): array
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

        $waybill = WaybillDetail::query()->create([
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

        return compact('booking', 'waybill');
    }

    public function test_soft_deleted_waybill_excludes_its_containers_from_remaining_count(): void
    {
        ['booking' => $booking, 'waybill' => $waybill] = $this->seedBookingGraph();

        Container::query()->create([
            'booking_id' => $booking->id,
            'waybill_id' => $waybill->id,
            'container_number' => 'CNT-1',
        ]);

        $booking->refresh();
        $this->assertSame(1, (int) $booking->activeBookingContainers()->count());

        $waybill->delete();

        $booking->refresh();
        $this->assertSame(0, (int) $booking->activeBookingContainers()->count());

        $payload = (new BookingService())->remainingContainer((int) $booking->id);
        $this->assertSame(10, $payload['expected_container']);
        $this->assertSame(0, $payload['containers_count']);
        $this->assertSame(10, $payload['remaining_container']);
    }

    public function test_restored_waybill_counts_its_containers_again(): void
    {
        ['booking' => $booking, 'waybill' => $waybill] = $this->seedBookingGraph();

        Container::query()->create([
            'booking_id' => $booking->id,
            'waybill_id' => $waybill->id,
            'container_number' => 'CNT-2',
        ]);

        $waybill->delete();
        $booking->refresh();
        $this->assertSame(0, (int) $booking->activeBookingContainers()->count());

        WaybillDetail::withTrashed()->whereKey($waybill->id)->firstOrFail()->restore();

        $booking->refresh();
        $this->assertSame(1, (int) $booking->activeBookingContainers()->count());
    }

    public function test_force_delete_removes_linked_containers(): void
    {
        ['booking' => $booking, 'waybill' => $waybill] = $this->seedBookingGraph();

        Container::query()->create([
            'booking_id' => $booking->id,
            'waybill_id' => $waybill->id,
            'container_number' => 'CNT-3',
        ]);

        $waybill->delete();

        app(WaybillDetailService::class)->forceDelete((int) $waybill->id);

        $this->assertDatabaseMissing('containers', [
            'booking_id' => $booking->id,
            'container_number' => 'CNT-3',
        ]);

        $booking->refresh();
        $this->assertSame(0, (int) $booking->containers()->count());
    }
}
