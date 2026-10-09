<?php

namespace App\Http\Controllers;

use App\Enums\DamageType;
use App\Enums\FuelLevel;
use App\Enums\VehicleCondition;
use App\Http\Requests\DeliveryContractRequest;
use App\Http\Requests\ReturnInspectionRequest;
use App\Models\Booking;
use App\Services\ContractService;
use App\Services\ContractSettings;
use App\Services\ContractTerms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Printable, signable pages for the pickup contract and the return inspection.
 */
class ContractController extends Controller
{
    public function __construct(
        protected ContractService $contracts,
        protected ContractSettings $settings,
        protected ContractTerms $terms,
    ) {
    }

    public function delivery(Booking $booking): View
    {
        $booking->load(['rental.car.make', 'rental.car.colors', 'rental.car.transmission', 'rental.car.fuel', 'customer', 'payment', 'contract']);

        $contract = $this->contracts->contractFor($booking);

        return view('contracts.delivery', $this->shared($booking) + [
            'contract' => $contract,
            'title' => $this->settings->title(),
            'footerNote' => $this->settings->footerNote(),
            'terms' => $contract->isSigned() && $contract->terms_snapshot
                ? ContractTerms::clean($contract->terms_snapshot)
                : $this->terms->render($booking, $contract->signer_document),
        ]);
    }

    public function saveDelivery(DeliveryContractRequest $request, Booking $booking): RedirectResponse
    {
        $sign = $request->input('action') === 'sign';

        return $this->attempt(
            fn () => $this->contracts->saveDelivery($booking, $request->validated() + $request->only(['customer_signature']), $request->file('pickup_images', []), $sign),
            route('contracts.delivery', $booking),
            $sign ? 'Contrato firmado. El vehículo quedó como Entregado.' : 'Contrato guardado.',
        );
    }

    public function voidDelivery(Booking $booking): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->contracts->voidDelivery($booking),
            route('contracts.delivery', $booking),
            'Las firmas fueron anuladas y la reserva volvió a Confirmada. Ya puedes editar el contrato.',
        );
    }

    public function return(Booking $booking): View|RedirectResponse
    {
        $booking->load(['rental.car', 'customer', 'contract']);

        if (! $booking->contract?->isSigned()) {
            return redirect()->route('contracts.delivery', $booking)
                ->with('error_msg', 'Primero hay que firmar el contrato de entrega del vehículo.');
        }

        $contract = $booking->contract;

        if (! $contract->isReturned()) {
            $contract->return_signer_name ??= $contract->signer_name;
            $contract->return_company_representative ??= $this->settings->companyRepresentative();
        }

        return view('contracts.return', $this->shared($booking) + [
            'contract' => $contract,
            'title' => 'Acta de Devolución del Vehículo',
        ]);
    }

    public function saveReturn(ReturnInspectionRequest $request, Booking $booking): RedirectResponse
    {
        $sign = $request->input('action') === 'sign';

        return $this->attempt(
            fn () => $this->contracts->saveReturn($booking, $request->validated() + $request->only(['customer_signature']), $request->file('return_images', []), $sign),
            route('contracts.return', $booking),
            $sign ? 'Devolución firmada. La renta se marcó como Completada.' : 'Revisión de devolución guardada.',
        );
    }

    public function voidReturn(Booking $booking): RedirectResponse
    {
        return $this->attempt(
            fn () => $this->contracts->voidReturn($booking),
            route('contracts.return', $booking),
            'Las firmas de la devolución fueron anuladas. La renta volvió a "Entregado".',
        );
    }

    protected function attempt(callable $callback, string $redirectTo, string $success): RedirectResponse
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            return redirect($redirectTo)->withInput()->with('error_msg', collect($exception->errors())->flatten()->first());
        }

        return redirect($redirectTo)->with('success_msg', $success);
    }

    /**
     * Data both documents need.
     */
    protected function shared(Booking $booking): array
    {
        return [
            'booking' => $booking,
            'company' => $this->settings->company(),
            'checklistItems' => $this->settings->checklistItems(),
            'minPhotos' => $this->settings->minPhotos(),
            'fuelLevels' => collect(FuelLevel::cases())->mapWithKeys(fn (FuelLevel $l) => [$l->value => $l->getLabel()])->all(),
            'conditionLevels' => collect(VehicleCondition::cases())->mapWithKeys(fn (VehicleCondition $c) => [$c->value => $c->getLabel()])->all(),
            'damageTypes' => collect(DamageType::cases())->mapWithKeys(fn (DamageType $d) => [$d->value => [
                'code' => $d->code(),
                'color' => $d->hex(),
                'label' => $d->getLabel(),
            ]])->all(),
        ];
    }
}
