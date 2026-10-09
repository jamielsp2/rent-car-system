<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Setting;
use App\Services\BookingCheckout;
use App\Services\RentalQuote;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, string $key, RentalQuote $quotes): View|RedirectResponse
    {
        $car = $this->car($key);
        $dates = $request->validate([
            'from' => ['required', 'date', 'after_or_equal:' . LocalTime::today()->toDateString()],
            'to' => ['required', 'date', 'after:from'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer'],
        ]);

        $quote = $quotes->quote($car, Carbon::parse($dates['from']), Carbon::parse($dates['to']), $dates['services'] ?? []);

        return view('site.checkout', [
            'car' => $car,
            'dates' => $dates,
            'quote' => $quote,
            // Only the extras that are still offered.
            'selected' => array_column($quote['services'], 'id'),
            'methods' => $this->paymentMethods(),
        ]);
    }

    public function store(Request $request, string $key, BookingCheckout $checkout): RedirectResponse
    {
        $car = $this->car($key);
        $methods = $this->paymentMethods();

        $data = $request->validate([
            'from' => ['required', 'date', 'after_or_equal:' . LocalTime::today()->toDateString()],
            'to' => ['required', 'date', 'after:from'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'min:7', 'max:25'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(array_keys($methods))],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Debes aceptar los términos y condiciones del alquiler.',
        ], [
            'name' => 'nombre', 'email' => 'correo', 'phone' => 'teléfono', 'pickup_time' => 'hora de entrega',
        ]);

        $from = Carbon::parse($data['from'] . ' ' . $data['pickup_time']);
        $to = Carbon::parse($data['to'] . ' ' . $data['pickup_time']);

        try {
            $booking = $checkout->book($car, $from, $to, $data, PaymentMethod::from($data['payment_method']), $data['services'] ?? []);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return redirect()->route('site.booking', $booking->transaction_id);
    }

    public function confirmation(string $transaction): View
    {
        $booking = Booking::query()
            ->with(['rental.car.slug', 'payment'])
            ->where('transaction_id', $transaction)
            ->firstOrFail();

        return view('site.booking-confirmed', [
            'booking' => $booking,
            'instructions' => $this->paymentMethods()[$booking->payment?->payment_channel?->value]['instructions'] ?? null,
        ]);
    }

    protected function car(string $key): Car
    {
        return Car::query()
            ->published()
            ->with(['make', 'type', 'slug'])
            ->whereHas('slug', fn (Builder $q) => $q->where('key', $key)->where('prefix', 'cars'))
            ->firstOrFail();
    }

    /**
     * Payment methods enabled in the shared settings.
     *
     * @return array<string, array{label: string, instructions: ?string}>
     */
    protected function paymentMethods(): array
    {
        return collect([PaymentMethod::CashOnDelivery, PaymentMethod::BankTransfer])
            ->filter(fn (PaymentMethod $method) => Setting::get("payment_{$method->value}_status"))
            ->mapWithKeys(fn (PaymentMethod $method) => [$method->value => [
                'label' => $method->getLabel(),
                'instructions' => trim(strip_tags((string) Setting::get("payment_{$method->value}_description"))) ?: null,
            ]])
            ->all();
    }
}
