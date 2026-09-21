<?php

namespace Tests\Unit\Services;

use App\Models\BillingStatement;
use App\Models\Booking;
use App\Models\ContainerYard;
use App\Models\Invoice;
use App\Models\ShippingLine;
use App\Models\StatementOfAccount;
use App\Models\User;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceDestroyInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceService();
        Carbon::setTestNow('2026-09-17 15:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_soft_delete_invoice_reopens_bookings_and_unmarks_billing_paid(): void
    {
        ['booking' => $booking, 'billing' => $billing, 'invoice' => $invoice] = $this->seedInvoicedSoa();

        $this->assertTrue((bool) $booking->fresh()->is_complete);
        $this->assertTrue((bool) $billing->fresh()->is_paid);

        $this->service->destroyInvoice($invoice->id);

        $booking->refresh();
        $this->assertFalse((bool) $booking->is_complete);
        $this->assertTrue($booking->auto_complete_at->equalTo(
            now()->addWeeks(Booking::AUTO_COMPLETE_WEEKS)
        ));
        $this->assertFalse((bool) $billing->fresh()->is_paid);
        $this->assertNotNull($invoice->fresh()->deleted_at);
    }

    public function test_soft_delete_invoice_does_not_reopen_booking_still_covered_by_another_invoice(): void
    {
        $shipping = $this->createShippingLine();
        [$from, $to] = $this->createYards();

        $keepBooking = $this->createBooking($shipping->id, $from->id, $to->id, 'KEEP');
        $dropBooking = $this->createBooking($shipping->id, $from->id, $to->id, 'DROP');

        $keepSoa = $this->createSoa($shipping->id, '2001', [$keepBooking->id]);
        $dropSoa = $this->createSoa($shipping->id, '2002', [$dropBooking->id]);

        $keepInvoice = $this->service->generateInvoice([
            'statement_of_account_ids' => [$keepSoa->id],
            'invoice_number' => 'INV-KEEP',
        ]);
        $dropInvoice = $this->service->generateInvoice([
            'statement_of_account_ids' => [$dropSoa->id],
            'invoice_number' => 'INV-DROP',
        ]);
        $this->service->markAsPaid($keepInvoice->id);
        $this->service->markAsPaid($dropInvoice->id);

        $this->service->destroyInvoice($dropInvoice->id);

        $this->assertTrue((bool) $keepBooking->fresh()->is_complete);
        $this->assertFalse((bool) $dropBooking->fresh()->is_complete);
        $this->assertNull($keepInvoice->fresh()->deleted_at);
    }

    public function test_invoice_is_paid_is_false_until_billing_is_marked_paid(): void
    {
        $user = User::factory()->create();
        $shipping = $this->createShippingLine();
        [$from, $to] = $this->createYards();
        $booking = $this->createBooking($shipping->id, $from->id, $to->id, 'PAID');
        $soa = $this->createSoa($shipping->id, '2001', [$booking->id]);

        BillingStatement::query()->create([
            'statement_of_account_id' => $soa->id,
            'prepared_by' => $user->id,
            'billing_statement_no' => 'B-UNPAID-1',
            'is_paid' => false,
        ]);

        $invoice = $this->service->generateInvoice([
            'statement_of_account_ids' => [$soa->id],
            'invoice_number' => 'INV-UNPAID-1',
        ]);

        $this->assertFalse($invoice->isPaid());
        $this->assertSame([$invoice->id => false], Invoice::paidStatusMap([$invoice]));

        $this->service->markAsPaid($invoice->id);
        $invoice->refresh()->load('statementOfAccounts');

        $this->assertTrue($invoice->isPaid());
        $this->assertSame([$invoice->id => true], Invoice::paidStatusMap([$invoice]));
    }

    public function test_soft_delete_keeps_booking_complete_if_another_soa_still_invoices_it(): void
    {
        $shipping = $this->createShippingLine();
        [$from, $to] = $this->createYards();
        $booking = $this->createBooking($shipping->id, $from->id, $to->id, 'SHARED');

        $soaA = $this->createSoa($shipping->id, '3001', [$booking->id]);
        $soaB = $this->createSoa($shipping->id, '3002', [$booking->id]);

        $invoiceA = $this->service->generateInvoice([
            'statement_of_account_ids' => [$soaA->id],
            'invoice_number' => 'INV-A',
        ]);
        $invoiceB = $this->service->generateInvoice([
            'statement_of_account_ids' => [$soaB->id],
            'invoice_number' => 'INV-B',
        ]);
        $this->service->markAsPaid($invoiceA->id);
        $this->service->markAsPaid($invoiceB->id);

        $this->service->destroyInvoice($invoiceB->id);

        $this->assertTrue((bool) $booking->fresh()->is_complete);
    }

    /**
     * @return array{booking: Booking, billing: BillingStatement, invoice: \App\Models\Invoice}
     */
    private function seedInvoicedSoa(): array
    {
        $user = User::factory()->create();
        $shipping = $this->createShippingLine();
        [$from, $to] = $this->createYards();
        $booking = $this->createBooking($shipping->id, $from->id, $to->id, 'MAIN');
        $soa = $this->createSoa($shipping->id, '1001', [$booking->id]);

        $billing = BillingStatement::query()->create([
            'statement_of_account_id' => $soa->id,
            'prepared_by' => $user->id,
            'billing_statement_no' => 'B-TEST-1',
            'is_paid' => false,
        ]);

        $invoice = $this->service->generateInvoice([
            'statement_of_account_ids' => [$soa->id],
            'invoice_number' => 'INV-TEST-1',
        ]);
        $this->service->markAsPaid($invoice->id);

        return compact('booking', 'billing', 'invoice');
    }

    private function createShippingLine(): ShippingLine
    {
        return ShippingLine::query()->create([
            'name' => 'Test Line',
            'email_address' => 'line@test.local',
        ]);
    }

    /**
     * @return array{0: ContainerYard, 1: ContainerYard}
     */
    private function createYards(): array
    {
        $from = ContainerYard::query()->create([
            'name' => 'CY From',
            'short_name' => 'CF',
            'location_type' => 'Container Yard',
            'is_active' => 1,
        ]);
        $to = ContainerYard::query()->create([
            'name' => 'CY To',
            'short_name' => 'CT',
            'location_type' => 'Container Yard',
            'is_active' => 1,
        ]);

        return [$from, $to];
    }

    private function createBooking(int $shippingLineId, int $fromId, int $toId, string $suffix): Booking
    {
        return Booking::query()->create([
            'reference_number' => 'REF-' . $suffix . '-' . uniqid(),
            'vessel' => 'Vessel',
            'shipping_line_id' => $shippingLineId,
            'cypa_id_from' => $fromId,
            'cypa_id_to' => $toId,
            'expected_date' => now()->toDateString(),
            'expected_container' => 1,
            'is_complete' => false,
        ]);
    }

    /**
     * @param array<int> $bookingIds
     */
    private function createSoa(int $shippingLineId, string $dliSaNumber, array $bookingIds): StatementOfAccount
    {
        return StatementOfAccount::query()->create([
            'shipping_line_id' => $shippingLineId,
            'dli_sa_number' => $dliSaNumber,
            'booking_ids' => $bookingIds,
            'work_order' => 'WO-' . $dliSaNumber,
        ]);
    }
}
