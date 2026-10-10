<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasTranslations;
use App\Enums\BookingStatus;
use App\Enums\CarStatus;
use App\Support\Media;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Table('cr_cars')]
class Car extends Model
{
    use HasSeo, HasTranslations;

    /** Columns with a version in other languages (see HasTranslations). */
    protected array $translatable = ['name', 'description', 'content'];

    protected $fillable = [
        'name',
        'description',
        'images',
        'license_plate',
        'vin',
        'make_id',
        'model_name',
        'year',
        'mileage',
        'status',
        'number_of_seats',
        'number_of_doors',
        'rental_rate',
        'deposit_amount',
        'min_rental_days',
        'is_featured',
        'insurance_info',
        'content',
        'vehicle_type_id',
        'transmission_id',
        'fuel_type_id',
    ];

    protected static function booted(): void
    {
        // Take the public address and the costs along when a car is deleted.
        static::deleting(function (Car $car): void {
            $car->slug()->delete();
            $car->expenses()->delete();
        });

        // A new car is approved and credited to the staff member who created it;
        // without approval it stays off the web.
        static::creating(function (Car $car): void {
            $car->moderation_status ??= 'approved';
            $car->author_id ??= auth()->id();
            $car->author_type ??= User::class;
            $car->currency_id ??= Currency::default()?->getKey();
        });
    }

    /**
     * Photos are stored as a JSON object keyed from "1" ({"1": "a.png", ...}).
     */
    public static function packImages(?array $paths): array
    {
        $paths = array_values(array_filter(array_map(Media::path(...), $paths ?? [])));

        return $paths ? array_combine(range(1, count($paths)), $paths) : [];
    }

    /**
     * Give the car a unique /cars/{key} address.
     */
    public function syncSlug(?string $key = null): void
    {
        $base = Str::slug($key ?: $this->name) ?: 'vehiculo';
        $key = $base;

        for ($i = 2; Slug::query()->where('prefix', 'cars')->where('key', $key)
            ->where(fn ($q) => $q->where('reference_type', '!=', Slug::CAR)->orWhere('reference_id', '!=', $this->getKey()))
            ->exists(); $i++) {
            $key = "{$base}-{$i}";
        }

        Slug::query()->updateOrCreate(
            ['reference_type' => Slug::CAR, 'reference_id' => $this->getKey()],
            ['key' => $key, 'prefix' => 'cars'],
        );

        $this->unsetRelation('slug');
    }

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'status' => CarStatus::class,
            'rental_rate' => 'float',
            'deposit_amount' => 'decimal:2',
            'is_featured' => 'boolean',
            'year' => 'integer',
            'mileage' => 'integer',
            'min_rental_days' => 'integer',
        ];
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(CarMake::class, 'make_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CarType::class, 'vehicle_type_id');
    }

    public function slug(): HasOne
    {
        return $this->hasOne(Slug::class, 'reference_id')->where('reference_type', Slug::CAR);
    }

    /**
     * Cars shown on the public site.
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', CarStatus::Available)->where('moderation_status', 'approved');
    }

    /**
     * Cars with no active booking overlapping the requested period. Compared by
     * day, like the calendar: a car returned on the 29th can go out again that day.
     */
    public function scopeAvailableBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->whereDoesntHave('bookingCars', fn (Builder $rental) => $rental
            ->whereDate('rental_start_date', '<', $to->toDateString())
            ->whereDate('rental_end_date', '>', $from->toDateString())
            ->whereHas('booking', fn (Builder $booking) => $booking->whereIn('status', [
                BookingStatus::Pending,
                BookingStatus::Confirmed,
                BookingStatus::Delivered,
            ])));
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CarExpense::class, 'car_id');
    }

    /**
     * Adds year_income, year_expenses, year_profit and year_rented_days for a
     * calendar year. Income is the rental price (no ITBIS, no extras) of
     * confirmed, delivered or completed bookings (pending ones may never
     * happen), split by the days that fall inside the year.
     */
    public function scopeWithYearStats(Builder $query, int $year): void
    {
        $start = "{$year}-01-01";
        $end = ($year + 1) . '-01-01';

        // Days of each rental inside the year, and the share of its price they represent.
        $daysInYear = 'GREATEST(0, DATEDIFF(LEAST(DATE(bc.rental_end_date), ?), GREATEST(DATE(bc.rental_start_date), ?)))';
        $totalDays = 'GREATEST(1, DATEDIFF(DATE(bc.rental_end_date), DATE(bc.rental_start_date)))';
        $rentals = 'FROM cr_booking_cars bc JOIN cr_bookings b ON b.id = bc.booking_id'
            . " WHERE bc.car_id = cr_cars.id AND b.status IN ('processing', 'delivered', 'completed')"
            . ' AND bc.rental_start_date < ? AND bc.rental_end_date > ?';

        $income = "COALESCE((SELECT SUM(bc.price * {$daysInYear} / {$totalDays}) {$rentals}), 0)";
        $incomeBindings = [$end, $start, $end, $start];
        $expenses = 'COALESCE((SELECT SUM(e.amount) FROM cr_car_maintenance_histories e WHERE e.car_id = cr_cars.id AND e.date >= ? AND e.date < ?), 0)';
        $expenseBindings = [$start, $end];

        $query
            ->select('cr_cars.*')
            ->selectRaw("{$income} AS year_income", $incomeBindings)
            ->selectRaw("COALESCE((SELECT SUM({$daysInYear}) {$rentals}), 0) AS year_rented_days", [$end, $start, $end, $start])
            ->selectRaw("{$expenses} AS year_expenses", $expenseBindings)
            ->selectRaw("{$income} - {$expenses} AS year_profit", [...$incomeBindings, ...$expenseBindings]);
    }

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(CarTransmission::class, 'transmission_id');
    }

    public function fuel(): BelongsTo
    {
        return $this->belongsTo(CarFuel::class, 'fuel_type_id');
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(CarColor::class, 'cr_cars_colors', 'cr_car_id', 'cr_car_color_id');
    }

    public function bookingCars(): HasMany
    {
        return $this->hasMany(BookingCar::class, 'car_id');
    }

    /**
     * Public page of the car, at /cars/{key}.
     */
    protected function publicUrl(): Attribute
    {
        return Attribute::get(fn () => $this->slug
            ? lroute('site.car', $this->slug->key)
            : lroute('site.cars'));
    }

    /**
     * Description written in the editor, limited to formatting tags.
     */
    protected function safeContent(): Attribute
    {
        return Attribute::get(function (): string {
            $html = strip_tags((string) $this->content, '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4>');

            return preg_replace('/\s[a-z-]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        });
    }

    protected function coverUrl(): Attribute
    {
        // Stored as a JSON object keyed from "1" ({"1": "a.png", ...}), not a list.
        return Attribute::get(fn () => Media::url(collect($this->images)->first()));
    }
}
