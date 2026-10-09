<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Validation\ValidationException;

class BookingStatusService
{
    public function __construct(protected ContractSettings $settings)
    {
    }

    /**
     * Why the booking cannot move to $to, or null when it can.
     */
    public function blocker(Booking $booking, BookingStatus $to): ?string
    {
        if ($booking->status === $to) {
            return null;
        }

        $contract = $booking->contract;

        if ($to === BookingStatus::Delivered) {
            if (! $contract?->isSigned()) {
                return 'No se puede marcar como Entregado: primero hay que firmar el contrato de entrega.';
            }

            $min = $this->settings->minPhotos();

            if (count($contract->pickup_images ?? []) < $min) {
                return "No se puede marcar como Entregado: el contrato debe tener al menos {$min} fotos del vehículo.";
            }
        }

        if ($to === BookingStatus::Completed && $contract?->isSigned() && ! $contract->isReturned()) {
            return 'No se puede completar: el vehículo se entregó con contrato. Haz y firma la revisión de devolución (al firmarla, la renta se completa sola).';
        }

        return null;
    }

    public function changeStatus(Booking $booking, BookingStatus $to): void
    {
        if ($blocker = $this->blocker($booking, $to)) {
            throw ValidationException::withMessages(['status' => $blocker]);
        }

        $booking->status = $to;

        if ($to === BookingStatus::Completed && ! $booking->completed_at) {
            $booking->completed_at = now();
        }

        $booking->save();
    }

    /**
     * Update the booking's payment and keep its invoice in sync.
     */
    public function changePaymentStatus(Booking $booking, PaymentStatus $to): void
    {
        $payment = $booking->payment;

        if (! $payment || $payment->status === $to) {
            return;
        }

        $payment->update(['status' => $to]);

        $booking->invoice?->update([
            'payment_id' => $payment->getKey(),
            'status' => $to->invoiceStatus(),
            'paid_at' => $to === PaymentStatus::Completed ? now() : null,
        ]);
    }
}
