<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingStatusService;
use App\Services\ContractService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Runs against rent_car_test (built by tests/TestCase.php); every test is rolled back.
 */
class ContractFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');

        // A pending booking with a payment and an invoice, without a contract yet.
        $this->booking = Booking::query()
            ->whereHas('payment')
            ->whereHas('rental')
            ->where('status', BookingStatus::Pending)
            ->firstOrFail();
        $this->booking->contract()->delete();
        $this->booking->unsetRelation('contract');

        $this->actingAs(User::query()->firstOrFail());
    }

    protected function photos(int $count): array
    {
        return collect(range(1, $count))->map(fn ($i) => UploadedFile::fake()->image("foto-{$i}.jpg", 40, 30))->all();
    }

    protected function signedDeliveryData(): array
    {
        return [
            'action' => 'sign',
            'pickup_mileage' => 45210,
            'pickup_fuel_level' => 'full',
            'checklist' => ['Gato', 'Extintor'],
            'damage_marks' => json_encode([['x' => 120, 'y' => 75, 'type' => 'scratch', 'note' => 'Puerta']]),
            'signer_name' => 'Cliente Prueba',
            'signer_document' => '001-0000000-1',
            'customer_signature' => self::SIGNATURE,
        ];
    }

    protected function deliver(): void
    {
        $this->post(route('contracts.delivery.save', $this->booking), $this->signedDeliveryData() + ['pickup_images' => $this->photos(4)])
            ->assertRedirect(route('contracts.delivery', $this->booking));

        $this->booking->refresh();
        $this->assertTrue($this->booking->contract->isSigned());
    }

    public function test_contract_pages_require_login(): void
    {
        auth()->logout();

        $this->get(route('contracts.delivery', $this->booking))->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_delivery_page_renders_with_rental_data(): void
    {
        $this->get(route('contracts.delivery', $this->booking))
            ->assertOk()
            ->assertSee($this->booking->booking_number)
            ->assertSee($this->booking->rental->car_name)
            ->assertSee('Diagrama de daños del vehículo')
            ->assertSee('1.- DEFINICIONES')
            ->assertSee('10.- COBROS')
            ->assertDontSee('Firma de la arrendadora');
    }

    public function test_return_page_requires_a_signed_contract(): void
    {
        $this->get(route('contracts.return', $this->booking))->assertRedirect(route('contracts.delivery', $this->booking));
    }

    public function test_signing_without_enough_photos_keeps_a_draft(): void
    {
        $this->post(route('contracts.delivery.save', $this->booking), $this->signedDeliveryData() + ['pickup_images' => $this->photos(1)])
            ->assertSessionHas('error_msg', fn ($msg) => str_contains($msg, 'al menos 4 fotos'));

        $contract = $this->booking->contract()->first();
        $this->assertNotNull($contract);
        $this->assertFalse($contract->isSigned());
        $this->assertCount(1, $contract->pickup_images);
        $this->assertSame(45210, $contract->pickup_mileage);
    }

    public function test_signing_stores_photos_marks_and_a_terms_snapshot(): void
    {
        $this->deliver();

        $contract = $this->booking->contract;
        $this->assertCount(4, $contract->pickup_images);
        Storage::disk('media')->assertExists($contract->pickup_images[0]);
        $this->assertSame('scratch', $contract->damage_marks[0]['type']);
        $this->assertStringContainsString('1.- DEFINICIONES', $contract->terms_snapshot);
        $this->assertSame('001-0000000-1', $contract->signer_document);
        $this->assertNull($contract->company_signature); // Only the customer signs.
    }

    public function test_signing_the_delivery_hands_the_vehicle_over(): void
    {
        $this->deliver();
        $this->assertSame(BookingStatus::Delivered, $this->booking->fresh()->status);

        // Voiding the contract takes the vehicle back out of "delivered".
        $this->post(route('contracts.delivery.void', $this->booking));
        $this->assertSame(BookingStatus::Confirmed, $this->booking->fresh()->status);
        $this->assertFalse($this->booking->contract()->first()->isSigned());
    }

    public function test_the_customer_signature_is_required(): void
    {
        $data = $this->signedDeliveryData();
        unset($data['customer_signature']);

        $this->post(route('contracts.delivery.save', $this->booking), $data + ['pickup_images' => $this->photos(4)])
            ->assertSessionHas('error_msg', fn ($msg) => str_contains($msg, 'firma del cliente'));

        $this->assertFalse($this->booking->contract()->first()->isSigned());
    }

    public function test_invalid_damage_marks_are_dropped(): void
    {
        $marks = app(ContractService::class)->sanitizeMarks(json_encode([
            ['x' => 10, 'y' => 10, 'type' => 'dent', 'note' => '<b>ok</b>'],
            ['x' => 9999, 'y' => 10, 'type' => 'dent'],
            ['x' => 10, 'y' => 10, 'type' => 'hack'],
        ]));

        $this->assertCount(1, $marks);
        $this->assertSame('ok', $marks[0]['note']);
    }

    public function test_delivered_status_requires_signed_contract(): void
    {
        $service = app(BookingStatusService::class);

        $this->assertNotNull($service->blocker($this->booking, BookingStatus::Delivered));

        $this->deliver();

        $service->changeStatus($this->booking, BookingStatus::Delivered);
        $this->assertSame(BookingStatus::Delivered, $this->booking->fresh()->status);
    }

    public function test_delivered_rental_completes_only_through_the_return(): void
    {
        $this->deliver();
        app(BookingStatusService::class)->changeStatus($this->booking, BookingStatus::Delivered);

        $this->expectException(ValidationException::class);
        app(BookingStatusService::class)->changeStatus($this->booking->fresh(), BookingStatus::Completed);
    }

    public function test_signing_the_return_completes_the_rental(): void
    {
        $this->deliver();
        app(BookingStatusService::class)->changeStatus($this->booking, BookingStatus::Delivered);

        $this->post(route('contracts.return.save', $this->booking), [
            'action' => 'sign',
            'return_mileage' => 46030,
            'return_fuel_level' => 'half',
            'return_checklist' => ['Gato'],
            'return_damage_marks' => json_encode([['x' => 455, 'y' => 250, 'type' => 'dent', 'note' => 'Techo']]),
            'return_extra_charges' => 1500,
            'return_charges_description' => 'Abolladura',
            'return_signer_name' => 'Cliente Prueba',
            'customer_signature' => self::SIGNATURE,
            'return_images' => $this->photos(4),
        ])->assertSessionHas('success_msg');

        $booking = $this->booking->fresh(['contract']);
        $this->assertSame(BookingStatus::Completed, $booking->status);
        $this->assertSame(46030, (int) $booking->completion_miles);
        $this->assertSame('half', $booking->completion_gas_level);
        $this->assertStringContainsString('Extintor', $booking->completion_notes);
        $this->assertSame(820, $booking->contract->drivenDistance());

        // The pickup contract cannot be voided once the return is signed.
        $this->post(route('contracts.delivery.void', $booking))->assertSessionHas('error_msg');
        $this->assertTrue($booking->contract->fresh()->isSigned());

        // Voiding the return puts the vehicle back with the customer.
        $this->post(route('contracts.return.void', $booking));
        $booking->refresh();
        $this->assertSame(BookingStatus::Delivered, $booking->status);
        $this->assertNotNull($booking->completed_at);
    }

    public function test_payment_status_change_updates_the_invoice(): void
    {
        $this->booking->payment->update(['status' => PaymentStatus::Pending]);

        app(BookingStatusService::class)->changePaymentStatus($this->booking->fresh(), PaymentStatus::Completed);

        $booking = $this->booking->fresh(['payment', 'invoice']);
        $this->assertSame(PaymentStatus::Completed, $booking->payment->status);

        if ($booking->invoice) {
            $this->assertSame(InvoiceStatus::Completed, $booking->invoice->status);
            $this->assertNotNull($booking->invoice->paid_at);
        }
    }

    public function test_admin_booking_pages_render(): void
    {
        $latest = Booking::query()->latest('id')->firstOrFail();

        $this->get(BookingResource::getUrl())->assertOk()->assertSee($latest->booking_number);
        $this->get(BookingResource::getUrl('edit', ['record' => $this->booking]))->assertOk()->assertSee($this->booking->customer_name);
    }
}
