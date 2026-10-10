<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Car;
use App\Models\CarFuel;
use App\Models\CarMake;
use App\Models\CarTransmission;
use App\Models\CarType;
use App\Models\Service;
use App\Models\Testimonial;
use App\Services\RentalQuote;
use App\Support\LocalTime;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SiteController extends Controller
{
    protected const CARD_RELATIONS = ['make', 'type', 'transmission', 'fuel', 'slug'];

    public function home(): View
    {
        return view('site.home', [
            'types' => $this->typesWithCounts(),
            'popular' => Car::query()
                ->published()
                ->with(self::CARD_RELATIONS)
                ->withCount('bookingCars')
                ->orderByDesc('is_featured')
                ->orderByDesc('booking_cars_count')
                ->orderBy('order')
                ->limit(8)
                ->get(),
            'testimonials' => Testimonial::featured(),
        ]);
    }

    public function cars(Request $request): View
    {
        // Search forms send empty values for "all" (type[]= ...); drop them instead of failing.
        $input = collect($request->query())
            ->map(fn ($value) => is_array($value) ? array_values(array_filter($value, fn ($v) => $v !== null && $v !== '')) : $value)
            ->reject(fn ($value) => $value === null || $value === '' || $value === [])
            ->all();

        // Invalid filters are ignored with a notice rather than bouncing the visitor away.
        $validator = validator($input, [
            'from' => ['nullable', 'date', 'after_or_equal:' . LocalTime::today()->toDateString()],
            'to' => ['nullable', 'date', 'after:from'],
            'type' => ['nullable', 'array'],
            'type.*' => ['integer'],
            'make' => ['nullable', 'array'],
            'make.*' => ['integer'],
            'transmission' => ['nullable', 'integer'],
            'fuel' => ['nullable', 'integer'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:recent,price_asc,price_desc,name'],
        ], [
            'from.after_or_equal' => __('La fecha de salida no puede ser anterior a hoy.'),
            'to.after' => __('La fecha de devolución debe ser después de la salida.'),
        ]);

        $filters = $validator->valid();

        if (isset($filters['to']) && ! isset($filters['from'])) {
            unset($filters['to']);
        }

        // Price filters are typed in the visitor's currency; rates are stored in the base one.
        $rate = Money::current()?->convert(1) ?: 1;

        $cars = Car::query()
            ->published()
            ->with(self::CARD_RELATIONS)
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->availableBetween(
                Carbon::parse($from),
                Carbon::parse($filters['to'] ?? Carbon::parse($from)->addDay()),
            ))
            ->when($filters['type'] ?? null, fn (Builder $q, $types) => $q->whereIn('vehicle_type_id', $types))
            ->when($filters['make'] ?? null, fn (Builder $q, $makes) => $q->whereIn('make_id', $makes))
            ->when($filters['transmission'] ?? null, fn (Builder $q, $id) => $q->where('transmission_id', $id))
            ->when($filters['fuel'] ?? null, fn (Builder $q, $id) => $q->where('fuel_type_id', $id))
            ->when(isset($filters['min_price']), fn (Builder $q) => $q->where('rental_rate', '>=', $filters['min_price'] / $rate))
            ->when(isset($filters['max_price']), fn (Builder $q) => $q->where('rental_rate', '<=', $filters['max_price'] / $rate))
            ->tap(fn (Builder $q) => match ($filters['sort'] ?? 'recent') {
                'price_asc' => $q->orderBy('rental_rate'),
                'price_desc' => $q->orderByDesc('rental_rate'),
                'name' => $q->orderBy('name'),
                default => $q->orderByDesc('id'),
            })
            ->paginate(12)
            ->withQueryString();

        return view('site.cars', [
            'cars' => $cars,
            'filters' => $filters,
            'notice' => $validator->errors()->first(),
            'types' => $this->typesWithCounts(),
            'makes' => CarMake::query()
                ->whereHas('cars', fn (Builder $q) => $q->published())
                ->withCount(['cars' => fn (Builder $q) => $q->published()])
                ->orderBy('name')
                ->get(),
            'transmissions' => CarTransmission::query()->orderBy('name')->get(),
            'fuels' => CarFuel::query()->orderBy('name')->get(),
            'maxRate' => (int) ceil((float) Car::query()->published()->max('rental_rate') * $rate),
            'minPrice' => ($min = Car::query()->published()->where('rental_rate', '>', 0)->min('rental_rate')) ? Money::display($min) : null,
            'heroImage' => Car::query()->published()->where('is_featured', true)->latest('id')->first()?->cover_url,
        ]);
    }

    public function car(Request $request, string $key, RentalQuote $quotes): View
    {
        $car = $this->publishedCar($key, ['make', 'type', 'transmission', 'fuel', 'colors', 'slug']);

        return view('site.car', $this->quoteData($request, $car, $quotes) + [
            'unavailable' => $this->bookedRanges($car),
            'deposit' => $quotes->deposit($car),
            'services' => Service::query()->published()->orderBy('name')->get(),
            'related' => Car::query()
                ->published()
                ->with(self::CARD_RELATIONS)
                ->whereKeyNot($car->getKey())
                ->where('vehicle_type_id', $car->vehicle_type_id)
                ->inRandomOrder()
                ->limit(4)
                ->get(),
        ]);
    }

    /**
     * Price box of the car page, refreshed as the visitor changes dates or extras.
     */
    public function quote(Request $request, string $key, RentalQuote $quotes): View
    {
        $car = $this->publishedCar($key, ['slug']);

        return view('site.partials.quote', $this->quoteData($request, $car, $quotes) + ['car' => $car]);
    }

    /**
     * Nights the car is already taken, for the calendar. The return day itself
     * stays free: a new rental can start when the previous one ends.
     *
     * @return array<int, array{from: string, to: string}>
     */
    protected function bookedRanges(Car $car): array
    {
        return $car->bookingCars()
            ->where('rental_end_date', '>', now())
            ->whereHas('booking', fn (Builder $q) => $q->whereIn('status', [
                BookingStatus::Pending,
                BookingStatus::Confirmed,
                BookingStatus::Delivered,
            ]))
            ->get(['rental_start_date', 'rental_end_date'])
            ->map(function ($rental): array {
                $from = $rental->rental_start_date->copy()->startOfDay();
                $to = $rental->rental_end_date->copy()->startOfDay()->subDay();

                return ['from' => $from->toDateString(), 'to' => $to->max($from)->toDateString()];
            })
            ->values()
            ->all();
    }

    protected function publishedCar(string $key, array $with): Car
    {
        return Car::query()
            ->published()
            ->with($with)
            ->whereHas('slug', fn (Builder $q) => $q->where('key', $key)->where('prefix', 'cars'))
            ->firstOrFail();
    }

    /**
     * @return array{car: Car, dates: array, selected: int[], quote: ?array, available: ?bool, error: ?string}
     */
    protected function quoteData(Request $request, Car $car, RentalQuote $quotes): array
    {
        $validator = validator($request->query(), [
            'from' => ['nullable', 'date', 'after_or_equal:' . LocalTime::today()->toDateString()],
            'to' => ['nullable', 'date', 'after:from'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer'],
        ], [
            'from.after_or_equal' => __('La fecha de salida no puede ser anterior a hoy.'),
            'to.after' => __('La devolución debe ser después de la salida.'),
        ]);

        $dates = $validator->valid();
        $selected = array_map('intval', $dates['services'] ?? []);
        $quote = null;
        $available = null;

        if ($validator->passes() && ! empty($dates['from']) && ! empty($dates['to'])) {
            $from = Carbon::parse($dates['from']);
            $to = Carbon::parse($dates['to']);
            $quote = $quotes->quote($car, $from, $to, $selected);
            $available = Car::query()->whereKey($car->getKey())->availableBetween($from, $to)->exists();
        }

        return [
            'car' => $car,
            'dates' => $dates,
            'selected' => $selected,
            'quote' => $quote,
            'available' => $available,
            'error' => $validator->errors()->first(),
        ];
    }

    /**
     * Visitor's currency for displayed prices (bookings are still charged in the base currency).
     */
    public function currency(Request $request, string $code): RedirectResponse
    {
        if (Money::currencies()->firstWhere('title', strtoupper($code))) {
            $request->session()->put('currency', strtoupper($code));
        }

        return redirect()->back(fallback: lroute('site.home'));
    }

    protected function typesWithCounts()
    {
        return CarType::query()
            ->withCount(['cars' => fn (Builder $q) => $q->published()])
            ->having('cars_count', '>', 0)
            ->orderBy('order')
            ->get();
    }
}
