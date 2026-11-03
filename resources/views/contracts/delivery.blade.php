@php
    $bookingCar = $booking->rental;
    $car = $bookingCar->car;
    $customer = $booking->customer;
    $signed = $contract->isSigned();
    $checked = $contract->checklist ?? [];
    $images = $contract->pickup_images ?? [];
    $value = fn (string $key) => old($key, $contract->{$key});
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __($title) }} - {{ $booking->booking_number }}</title>
    <style>
        @include('contracts.partials.document-styles')
    </style>
</head>
<body>
<div class="toolbar">
    <a class="btn btn-outline" href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $booking]) }}">&larr; {{ __('Volver a la reserva') }}</a>
    <button type="button" class="btn" onclick="window.print()">{{ __('Imprimir / Guardar PDF') }}</button>
    {{-- Language of the contract: saved on the contract, locked once it is signed. --}}
    <form class="lang-picker no-print" method="POST" action="{{ route('contracts.delivery.language', $booking) }}"
          @if ($signed) title="{{ __('El idioma quedó fijado al firmar.') }}" @endif>
        @csrf
        <select id="contract-language" name="language" aria-label="{{ __('Idioma del contrato') }}" onchange="this.form.submit()" @disabled($signed)>
            @foreach (\App\Support\Lang::SUPPORTED as $code => $name)
                <option value="{{ $code }}" @selected($language === $code)>{{ $name }}</option>
            @endforeach
        </select>
    </form>
    @if ($signed)
        <a class="btn btn-success" href="{{ route('contracts.return', $booking) }}">
            {{ $contract->isReturned() ? __('Ver revisión de devolución') : __('Revisión de devolución') }}
        </a>
    @endif
    @if ($signed && ! $contract->isReturned())
        <form method="POST" action="{{ route('contracts.delivery.void', $booking) }}" style="margin: 0"
              onsubmit="return confirm(@js(__('¿Anular las firmas? El contrato quedará editable y deberá firmarse de nuevo.')))">
            @csrf
            <button type="submit" class="btn btn-danger">{{ __('Anular firmas y editar') }}</button>
        </form>
    @endif
</div>

@if (session('success_msg'))
    <div class="alert alert-success">{{ session('success_msg') }}</div>
@endif
@if (session('error_msg'))
    <div class="alert alert-error">{{ session('error_msg') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach (array_unique($errors->all()) as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="contract-form" class="document-form" method="POST" action="{{ route('contracts.delivery.save', $booking) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="action" id="contract-action" value="save">
    <input type="hidden" name="customer_signature" id="customer_signature">

    <div class="page">
        <header class="contract-header">
            <div class="company-block">
                @if ($company['logo'])
                    <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" class="company-logo"><br>
                @endif
                <strong>{{ $company['name'] }}</strong><br>
                @if ($company['rnc']) {{ __('RNC') }}: {{ $company['rnc'] }}<br> @endif
                @if ($company['address']) {{ $company['address'] }}<br> @endif
                @if ($company['phone']) {{ $company['phone'] }} @endif
                @if ($company['email']) &middot; {{ $company['email'] }} @endif
            </div>
            <div class="contract-meta">
                <h1>{{ __($title) }}</h1>
                <div>{{ __('Contrato No.') }} <strong>{{ $booking->booking_number }}</strong></div>
                <div>{{ __('Fecha') }}: {{ \App\Support\Dates::date($contract->signed_at ?: now()) }}</div>
                @if ($signed)
                    <span class="badge badge-signed">{{ __('Firmado el :date', ['date' => \App\Support\Dates::dateTime($contract->signed_at)]) }}</span>
                @else
                    <span class="badge badge-unsigned">{{ __('Pendiente de firma') }}</span>
                @endif
            </div>
        </header>

        <section>
            <div class="grid">
                <div>
                    <h2>{{ __('La arrendadora') }}</h2>
                    <div class="row"><span class="label">{{ __('Nombre') }}</span><span class="val">{{ $company['name'] }}</span></div>
                    <div class="row"><span class="label">{{ __('RNC') }}</span><span class="val">{{ $company['rnc'] ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Dirección') }}</span><span class="val">{{ $company['address'] ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Teléfono') }}</span><span class="val">{{ $company['phone'] ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Correo') }}</span><span class="val">{{ $company['email'] ?: '—' }}</span></div>
                </div>
                <div>
                    <h2>{{ __('El arrendatario') }}</h2>
                    <div class="row"><span class="label">{{ __('Nombre') }}</span><span class="val">{{ $booking->customer_name }}</span></div>
                    <div class="row"><span class="label">{{ __('Cédula / Pasaporte') }}</span><span class="val">{{ $value('signer_document') ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Licencia de conducir') }}</span>
                        <span class="val">
                            {{ $customer->license_number ?: '—' }}
                            @if ($customer->license_expiry_date)
                                ({{ __('Vence') }} {{ \App\Support\Dates::date($customer->license_expiry_date) }})
                            @endif
                        </span>
                    </div>
                    <div class="row"><span class="label">{{ __('Teléfono') }}</span><span class="val">{{ $booking->customer_phone ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Correo') }}</span><span class="val">{{ $booking->customer_email ?: '—' }}</span></div>
                    @if ($customer->address)
                        <div class="row"><span class="label">{{ __('Dirección') }}</span><span class="val">{{ $customer->address }}</span></div>
                    @endif
                </div>
            </div>
        </section>

        <section>
            <h2>{{ __('Vehículo') }}</h2>
            <div class="grid">
                <div>
                    <div class="row"><span class="label">{{ __('Vehículo') }}</span><span class="val">{{ $bookingCar->car_name }}</span></div>
                    <div class="row"><span class="label">{{ __('Marca') }}</span><span class="val">{{ $car->make->name ?? '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Modelo') }}</span><span class="val">{{ $car->model_name ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Año') }}</span><span class="val">{{ $car->year ?: '—' }}</span></div>
                </div>
                <div>
                    <div class="row"><span class="label">{{ __('Placa') }}</span><span class="val">{{ $car->license_plate ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Chasis (VIN)') }}</span><span class="val">{{ $car->vin ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Color') }}</span><span class="val">{{ $car->colors->pluck('name')->implode(', ') ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ __('Transmisión') }} / {{ __('Combustible') }}</span>
                        <span class="val">{{ $car->transmission->name ?? '—' }} / {{ $car->fuel->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <h2>{{ __('Detalles de la renta') }}</h2>
            <div class="grid">
                <div>
                    <div class="row"><span class="label">{{ __('Reserva No.') }}</span><span class="val">{{ $booking->booking_number }}</span></div>
                    <div class="row"><span class="label">{{ __('Fecha de salida') }}</span><span class="val">{{ $bookingCar->rental_start_date?->format('d/m/Y h:i A') }}</span></div>
                    <div class="row"><span class="label">{{ __('Fecha de devolución') }}</span><span class="val">{{ $bookingCar->rental_end_date?->format('d/m/Y h:i A') }}</span></div>
                    <div class="row"><span class="label">{{ __('Días') }}</span><span class="val">{{ $bookingCar->days() }}</span></div>
                </div>
                <div>
                    <div class="row"><span class="label">{{ __('Subtotal') }}</span><span class="val text-right">{{ \App\Support\Money::format($booking->sub_total) }}</span></div>
                    @if ($booking->coupon_amount > 0)
                        <div class="row"><span class="label">{{ __('Descuento') }}</span><span class="val text-right">-{{ \App\Support\Money::format($booking->coupon_amount) }}</span></div>
                    @endif
                    @if ($booking->tax_amount > 0)
                        <div class="row"><span class="label">{{ __('Impuestos') }}</span><span class="val text-right">{{ \App\Support\Money::format($booking->tax_amount) }}</span></div>
                    @endif
                    <div class="row"><span class="label">{{ __('Total') }}</span><span class="val text-right">{{ \App\Support\Money::format($booking->amount) }}</span></div>
                    <div class="row"><span class="label">{{ __('Depósito de garantía') }}</span><span class="val text-right">{{ \App\Support\Money::format($booking->deposit_amount ?: 0) }}</span></div>
                    @if ($booking->payment)
                        <div class="row"><span class="label">{{ __('Estado del pago') }}</span><span class="val text-right">{{ $booking->payment->status?->getLabel() }}</span></div>
                    @endif
                </div>
            </div>
        </section>

        <fieldset @disabled($signed) style="margin: 0; padding: 0; border: 0; min-width: 0;">
            <section>
                <h2>{{ __('Estado del vehículo al momento de la entrega') }}</h2>
                <div class="grid">
                    <label class="field">
                        <span>{{ __('Kilometraje de salida') }}</span>
                        <input type="number" name="pickup_mileage" min="0" value="{{ $value('pickup_mileage') ?? $car->mileage }}">
                    </label>
                    <label class="field">
                        <span>{{ __('Nivel de combustible') }}</span>
                        <select name="pickup_fuel_level">
                            <option value="">—</option>
                            @foreach ($fuelLevels as $key => $label)
                                <option value="{{ $key }}" @selected($value('pickup_fuel_level') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>{{ __('Estado exterior') }}</span>
                        <select name="exterior_condition">
                            <option value="">—</option>
                            @foreach ($conditionLevels as $key => $label)
                                <option value="{{ $key }}" @selected($value('exterior_condition') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>{{ __('Estado interior') }}</span>
                        <select name="interior_condition">
                            <option value="">—</option>
                            @foreach ($conditionLevels as $key => $label)
                                <option value="{{ $key }}" @selected($value('interior_condition') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label class="field">
                    <span>{{ __('Daños existentes (golpes, rayones, etc.)') }}</span>
                    <textarea name="existing_damages" placeholder="{{ __('Ej.: rayón en puerta trasera derecha, abolladura pequeña en bumper delantero...') }}">{{ $value('existing_damages') }}</textarea>
                </label>
                <label class="field">
                    <span>{{ __('Observaciones') }}</span>
                    <textarea name="notes">{{ $value('notes') }}</textarea>
                </label>
            </section>

            <section>
                <h2>{{ __('Diagrama de daños del vehículo') }}</h2>
                @include('contracts.partials.vehicle-diagram', [
                    'damageTypes' => $damageTypes,
                    'marks' => (old('damage_marks') ? json_decode(old('damage_marks'), true) : null) ?: ($contract->damage_marks ?? []),
                    'editable' => ! $signed,
                ])
            </section>

            @if ($checklistItems)
                <section>
                    <h2>{{ __('Accesorios y documentos entregados') }}</h2>
                    <div class="checklist">
                        @foreach ($checklistItems as $item)
                            <label>
                                <input type="checkbox" name="checklist[]" value="{{ $item }}" @checked(in_array($item, old('checklist', $checked)))>
                                {{ __($item) }}
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($images || ! $signed)
                <section>
                    <h2>{{ __('Fotos del vehículo') }}</h2>
                    @if ($minPhotos > 0 && ! $signed)
                        <p class="photo-requirement no-print">
                            {{ __('Obligatorio: sube al menos :count fotos que muestren el estado del vehículo al entregarlo (frente, trasera, lado izquierdo y lado derecho).', ['count' => $minPhotos]) }}
                            <strong id="photo-count" data-min="{{ $minPhotos }}"></strong>
                        </p>
                    @endif
                    @if ($images)
                        <div class="photos">
                            @foreach ($images as $image)
                                <div class="photo">
                                    <a href="{{ \App\Support\Media::url($image) }}" target="_blank">
                                        <img src="{{ \App\Support\Media::url($image) }}" alt="">
                                    </a>
                                    @unless ($signed)
                                        <label class="no-print" title="&times;">
                                            <input type="checkbox" name="existing_images[]" value="{{ $image }}" checked> ✓
                                        </label>
                                    @endunless
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @unless ($signed)
                        <label class="field no-print">
                            <span>{{ __('Agregar fotos') }}</span>
                            <input type="file" name="pickup_images[]" accept="image/*" multiple>
                        </label>
                    @endunless
                </section>
            @endif
        </fieldset>

        <section>
            <h2>{{ __('Términos y condiciones') }}</h2>
            <div class="terms">{!! $terms !!}</div>
        </section>

        <section>
            <h2>{{ __('Firmas') }}</h2>
            <p class="declaration">{{ __('Declaro haber recibido el vehículo en las condiciones descritas y acepto los términos de este contrato, haciéndome responsable del vehículo durante el período de renta.') }}</p>

            <div class="signatures">
                <div>
                    @if ($signed)
                        <img class="sig-image" src="{{ $contract->customer_signature }}" alt="">
                    @else
                        <div class="sig-pad" data-target="customer_signature">
                            <canvas></canvas>
                            <div class="hint">{{ __('Firme aquí con el dedo, lápiz o mouse') }}</div>
                        </div>
                        <div class="sig-actions"><button type="button" class="btn btn-sm" data-clear="customer_signature">{{ __('Borrar firma') }}</button></div>
                    @endif
                    <div class="sig-line">
                        <strong>{{ __('Firma del arrendatario') }}</strong>
                        @if ($signed)
                            {{ $contract->signer_name }} &middot; {{ $contract->signer_document }}
                        @else
                            <div class="grid" style="margin-top: 6px; text-align: left;">
                                <label class="field">
                                    <span>{{ __('Nombre de quien firma') }}</span>
                                    <input type="text" name="signer_name" value="{{ $value('signer_name') }}">
                                </label>
                                <label class="field">
                                    <span>{{ __('Cédula / Pasaporte') }}</span>
                                    <input type="text" name="signer_document" value="{{ $value('signer_document') }}">
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if ($footerNote)
            <div class="footer-note">{!! nl2br(e($footerNote)) !!}</div>
        @endif

        @unless ($signed)
            <div class="form-actions no-print">
                <button type="submit" class="btn btn-primary" data-action="save">{{ __('Guardar sin firmar') }}</button>
                <button type="submit" class="btn btn-success" data-action="sign">{{ __('Firmar y guardar contrato') }}</button>
            </div>
        @endunless
    </div>
</form>

@unless ($signed)
    <script>
        @include('contracts.partials.document-scripts', ['photoInputName' => 'pickup_images[]'])
    </script>
@endunless
</body>
</html>
