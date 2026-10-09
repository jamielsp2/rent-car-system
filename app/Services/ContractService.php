<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DamageType;
use App\Models\Booking;
use App\Models\BookingContract;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pickup contract (delivery) and return inspection for a booking.
 */
class ContractService
{
    public const DIAGRAM_WIDTH = 700;

    public const DIAGRAM_HEIGHT = 350;

    public const SIGNATURE_PATTERN = '/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/';

    public function __construct(
        protected ContractSettings $settings,
        protected ContractTerms $terms,
    ) {
    }

    public function contractFor(Booking $booking): BookingContract
    {
        return $booking->contract ?? new BookingContract([
            'company_representative' => $this->settings->companyRepresentative(),
            'signer_name' => $booking->customer_name,
        ]);
    }

    /**
     * @param  UploadedFile[]  $uploads
     */
    public function saveDelivery(Booking $booking, array $data, array $uploads, bool $sign): BookingContract
    {
        $contract = $this->contractFor($booking);

        if ($contract->isSigned()) {
            throw ValidationException::withMessages(['contract' => 'El contrato ya está firmado. Anula las firmas para editarlo.']);
        }

        $images = array_merge($data['existing_images'] ?? [], $this->store($uploads, 'car-manager/contract-images'));

        $contract->fill([
            'pickup_mileage' => $data['pickup_mileage'] ?? null,
            'pickup_fuel_level' => $data['pickup_fuel_level'] ?? null,
            'exterior_condition' => $data['exterior_condition'] ?? null,
            'interior_condition' => $data['interior_condition'] ?? null,
            'existing_damages' => $data['existing_damages'] ?? null,
            'notes' => $data['notes'] ?? null,
            'checklist' => array_values($data['checklist'] ?? []),
            'damage_marks' => $this->sanitizeMarks($data['damage_marks'] ?? null),
            'pickup_images' => array_values($images),
            'signer_name' => $data['signer_name'] ?? null,
            'signer_document' => $data['signer_document'] ?? null,
            'company_representative' => $data['company_representative'] ?? $contract->company_representative,
        ]);

        if ($sign) {
            $this->saveDraftUnlessSignable($booking, $contract, fn () => $this->assertCanSign(count($images), $data));

            $contract->fill([
                'customer_signature' => $data['customer_signature'],
                'signed_at' => now(),
                // Freeze the clauses as they read when the customer signed.
                'terms_snapshot' => $this->terms->render($booking, $data['signer_document'] ?? null),
            ]);
        }

        $booking->contract()->save($contract);

        // Signing is handing over the keys (the photos it requires are the delivery
        // rule), so the booking moves to Delivered by itself, as the return does.
        if ($sign && in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            $booking->update(['status' => BookingStatus::Delivered]);
        }

        return $contract;
    }

    public function voidDelivery(Booking $booking): void
    {
        $contract = $booking->contract;

        if ($contract?->isReturned()) {
            throw ValidationException::withMessages(['contract' => 'No se puede anular el contrato de entrega porque la devolución ya está firmada. Anula primero la devolución.']);
        }

        $contract?->update([
            'customer_signature' => null,
            'company_signature' => null,
            'terms_snapshot' => null,
            'signed_at' => null,
        ]);

        // Without a signed contract the vehicle is not delivered any more.
        if ($booking->status === BookingStatus::Delivered) {
            $booking->update(['status' => BookingStatus::Confirmed]);
        }
    }

    /**
     * @param  UploadedFile[]  $uploads
     */
    public function saveReturn(Booking $booking, array $data, array $uploads, bool $sign): BookingContract
    {
        $contract = $booking->contract;

        if (! $contract?->isSigned()) {
            throw ValidationException::withMessages(['contract' => 'Primero hay que firmar el contrato de entrega del vehículo.']);
        }

        if ($contract->isReturned()) {
            throw ValidationException::withMessages(['contract' => 'La devolución ya está firmada. Anula las firmas para editarla.']);
        }

        $images = array_merge($data['existing_images'] ?? [], $this->store($uploads, 'car-manager/return-images'));

        $contract->fill([
            'return_mileage' => $data['return_mileage'] ?? null,
            'return_fuel_level' => $data['return_fuel_level'] ?? null,
            'return_exterior_condition' => $data['return_exterior_condition'] ?? null,
            'return_interior_condition' => $data['return_interior_condition'] ?? null,
            'return_checklist' => array_values($data['return_checklist'] ?? []),
            'return_damage_marks' => $this->sanitizeMarks($data['return_damage_marks'] ?? null),
            'return_images' => array_values($images),
            'return_notes' => $data['return_notes'] ?? null,
            'return_extra_charges' => $data['return_extra_charges'] ?? null,
            'return_charges_description' => $data['return_charges_description'] ?? null,
            'return_signer_name' => $data['return_signer_name'] ?? null,
            'return_company_representative' => $data['return_company_representative'] ?? $contract->return_company_representative ?? $this->settings->companyRepresentative(),
        ]);

        if (! $sign) {
            $contract->save();

            return $contract;
        }

        $this->saveDraftUnlessSignable($booking, $contract, function () use ($data, $images): void {
            if (empty($data['return_mileage']) || empty($data['return_fuel_level'])) {
                throw ValidationException::withMessages(['return_mileage' => 'Indica el kilometraje y el combustible de llegada.']);
            }

            $this->assertCanSign(count($images), $data, 'return_signer_name');
        });

        DB::transaction(function () use ($booking, $contract, $data): void {
            $contract->fill([
                'return_customer_signature' => $data['customer_signature'],
                'returned_at' => now(),
            ])->save();

            $this->completeBooking($booking, $contract);
        });

        return $contract;
    }

    public function voidReturn(Booking $booking): void
    {
        $booking->contract?->update([
            'return_customer_signature' => null,
            'return_company_signature' => null,
            'returned_at' => null,
        ]);

        // Back with the customer until the return is signed again. completed_at
        // stays: it records that completion already happened once.
        if ($booking->status === BookingStatus::Completed) {
            $booking->update(['status' => BookingStatus::Delivered]);
        }
    }

    /**
     * Signing the return closes the rental and fills the booking's completion fields.
     */
    protected function completeBooking(Booking $booking, BookingContract $contract): void
    {
        $notes = array_filter([
            $contract->return_notes,
            $contract->return_extra_charges > 0
                ? 'Cargos adicionales: ' . Money::format($contract->return_extra_charges)
                    . ($contract->return_charges_description ? ' — ' . $contract->return_charges_description : '')
                : null,
            ($missing = $contract->missingItems()) ? 'Accesorios no devueltos: ' . implode(', ', $missing) : null,
        ]);

        $booking->update([
            'completion_miles' => $contract->return_mileage,
            'completion_gas_level' => $contract->return_fuel_level,
            'completion_damage_images' => $contract->return_images,
            'completion_notes' => implode("\n", $notes) ?: null,
            'completed_at' => $booking->completed_at ?: now(),
            'status' => BookingStatus::Completed,
        ]);
    }

    /**
     * A failed signing attempt still keeps what was entered (photos included) as a draft.
     */
    protected function saveDraftUnlessSignable(Booking $booking, BookingContract $contract, callable $check): void
    {
        try {
            $check();
        } catch (ValidationException $exception) {
            $booking->contract()->save($contract);

            throw $exception;
        }
    }

    protected function assertCanSign(int $photos, array $data, string $signerField = 'signer_name'): void
    {
        $min = $this->settings->minPhotos();

        if ($photos < $min) {
            throw ValidationException::withMessages(['photos' => "Para firmar se requieren al menos {$min} fotos del vehículo (hay {$photos})."]);
        }

        // Only the customer signs; the company's acceptance is the contract itself.
        if (! preg_match(self::SIGNATURE_PATTERN, (string) ($data['customer_signature'] ?? ''))) {
            throw ValidationException::withMessages(['customer_signature' => 'Se requiere la firma del cliente.']);
        }

        if (empty($data[$signerField])) {
            throw ValidationException::withMessages([$signerField => 'Indica el nombre de quien firma.']);
        }
    }

    /**
     * Keep only well-formed marks inside the diagram.
     */
    public function sanitizeMarks(mixed $marks): array
    {
        if (is_string($marks)) {
            $marks = json_decode($marks, true);
        }

        return collect(is_array($marks) ? $marks : [])
            ->filter(fn ($mark) => is_array($mark)
                && isset($mark['x'], $mark['y'], $mark['type'])
                && DamageType::tryFrom((string) $mark['type'])
                && is_numeric($mark['x']) && is_numeric($mark['y'])
                && $mark['x'] >= 0 && $mark['x'] <= self::DIAGRAM_WIDTH
                && $mark['y'] >= 0 && $mark['y'] <= self::DIAGRAM_HEIGHT)
            ->take(60)
            ->map(fn (array $mark) => [
                'x' => round((float) $mark['x'], 1),
                'y' => round((float) $mark['y'], 1),
                'type' => $mark['type'],
                'note' => Str::limit(trim(strip_tags((string) ($mark['note'] ?? ''))), 150, ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  UploadedFile[]  $uploads
     */
    protected function store(array $uploads, string $folder): array
    {
        return collect($uploads)
            ->filter(fn ($file) => $file instanceof UploadedFile && $file->isValid())
            ->map(fn (UploadedFile $file) => $file->store($folder, 'media'))
            ->filter()
            ->values()
            ->all();
    }
}
