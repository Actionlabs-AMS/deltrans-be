<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ContainerYard;
use App\Models\Driver;
use App\Models\FixedExpense;
use App\Models\FleetTruck;
use App\Models\ShippingLine;
use App\Models\WaybillDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingListSearchTest extends TestCase
{
    use RefreshDatabase;

    private ShippingLine $shippingLine;

    private ContainerYard $cypaFrom;

    private ContainerYard $cypaTo;

    private Driver $driver;

    private FleetTruck $truck;

    private FixedExpense $fixedExpense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $this->shippingLine = ShippingLine::query()->create([
            'name' => 'Search Test Line',
            'email_address' => 'search-line@test.local',
        ]);
        $this->cypaFrom = ContainerYard::query()->create([
            'name' => 'Search CY From',
            'short_name' => 'SCF',
            'location_type' => 'Container Yard',
            'is_active' => 1,
        ]);
        $this->cypaTo = ContainerYard::query()->create([
            'name' => 'Search CY To',
            'short_name' => 'SCT',
            'location_type' => 'Container Yard',
            'is_active' => 1,
        ]);
        $this->driver = Driver::query()->create([
            'first_name' => 'Search',
            'last_name' => 'Driver',
            'contact_number' => '000',
            'is_active' => 1,
        ]);
        $this->truck = FleetTruck::query()->create([
            'plate_number' => 'SRC-1000',
            'condition' => 'good',
            'is_active' => 1,
        ]);
        $this->fixedExpense = FixedExpense::query()->create([
            'shipping_line_id' => $this->shippingLine->id,
            'cypa_id_from' => $this->cypaFrom->id,
            'cypa_id_to' => $this->cypaTo->id,
            'container_size' => '20',
        ]);
    }

    public function test_id_finds_a_booking_by_booking_number(): void
    {
        $matchingBooking = $this->createBooking('BOOKING-SEARCH-100');
        $this->createBooking('BOOKING-OTHER-200');

        $response = $this->getJson('/api/bookings?id=SEARCH-100');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBooking->id)
            ->assertJsonPath('data.0.reference_number', 'BOOKING-SEARCH-100')
            ->assertJsonMissingPath('data.0.waybill_number');
    }

    public function test_id_finds_and_returns_the_booking_related_to_a_waybill_number(): void
    {
        $matchingBooking = $this->createBooking('BOOKING-WITH-WAYBILL');
        $otherBooking = $this->createBooking('BOOKING-WITHOUT-MATCH');
        $this->createWaybill($matchingBooking, 'WAYBILL-SEARCH-321');
        $this->createWaybill($otherBooking, 'WAYBILL-OTHER-654');

        $response = $this->getJson('/api/bookings?id=SEARCH-321');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBooking->id)
            ->assertJsonPath('data.0.reference_number', 'BOOKING-WITH-WAYBILL')
            ->assertJsonMissingPath('data.0.waybill_number');
    }

    public function test_id_does_not_match_other_booking_fields(): void
    {
        $this->createBooking('BOOKING-NO-ID-MATCH');

        $response = $this->getJson('/api/bookings?id=Search%20Vessel');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function createBooking(string $referenceNumber): Booking
    {
        return Booking::query()->create([
            'reference_number' => $referenceNumber,
            'vessel' => 'Search Vessel',
            'shipping_line_id' => $this->shippingLine->id,
            'cypa_id_from' => $this->cypaFrom->id,
            'cypa_id_to' => $this->cypaTo->id,
            'expected_date' => now()->toDateString(),
            'expected_container' => 1,
        ]);
    }

    private function createWaybill(Booking $booking, string $waybillNumber): WaybillDetail
    {
        return WaybillDetail::query()->create([
            'waybill_number' => $waybillNumber,
            'transaction_date' => now()->toDateString(),
            'shipping_line_id' => $this->shippingLine->id,
            'booking_id' => $booking->id,
            'driver_id' => $this->driver->id,
            'container_size' => '20',
            'truck_plate_number' => $this->truck->plate_number,
            'pickup_date' => now()->toDateString(),
            'delivered_date' => now()->toDateString(),
            'no_of_days' => 1,
            'stack_run' => 0,
            'rate' => 100,
            'fixed_expense_id' => $this->fixedExpense->id,
        ]);
    }
}
