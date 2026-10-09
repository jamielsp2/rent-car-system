@php
    $bookingCar = $booking->rental;
    $car = $bookingCar->car;
    $returned = $contract->isReturned();
    $delivered = $contract->checklist ?? [];
    $returnedItems = old('return_checklist', $contract->return_checklist ?? ($returned ? [] : $delivered));
    $images = $contract->return_images ?? [];
    $value = fn (string $key) => old($key, $contract->{$key});
    $fuelOrder = array_keys($fuelLevels);
    $fuelLabel = fn (?string $key) => $key ? ($fuelLevels[$key] ?? $key) : '—';
    $conditionLabel = fn (?string $key) => $key ? ($conditionLevels[$key] ?? $key) : '—';
    $driven = $contract->drivenDistance();
    $pickupFuelIndex = array_search($contract->pickup_fuel_level, $fuelOrder, true);
    $returnFuelIndex = array_search($contract->return_fuel_level, $fuelOrder, true);
    $referenceMarks = $contract->damage_marks ?? [];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ $booking->booking_number }}</title>
    <style>
        @include('contracts.partials.document-styles')

        .compare td, .compare th { vertical-align: middle; }
        .compare td.num { width: 22%; }
        .compare select, .compare input { margin: 0; }
        .diff-bad { color: var(--danger); font-weight: 700; }
        .diff-ok { color: var(--ok); font-weight: 700; }
        .missing-item { color: var(--danger); font-weight: 600; }
        .reference-list { margin: 6px 0 0; padding-left: 18px; color: var(--muted); font-size: 12px; }
        .photos-ref img { width: 90px; height: 68px; opacity: .85; }
        .charges-box { display: grid; grid-template-columns: 220px 1fr; gap: 0 18px; }
        @media (max-width: 720px) { .charges-box { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="toolbar">
    <a class="btn btn-outline" href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $booking]) }}">&larr; {{ 'Volver a la reserva' }}</a>
    <a class="btn btn-outline" href="{{ route('contracts.delivery', $booking) }}">{{ 'Ver contrato de entrega' }}</a>
    <button type="button" class="btn" onclick="window.print()">{{ 'Imprimir / Guardar PDF' }}</button>
    @if ($returned)
        <form method="POST" action="{{ route('contracts.return.void', $booking) }}" style="margin: 0"
              onsubmit="return confirm(@js('¿Anular las firmas de la devolución? La renta volverá a "Entregado" hasta que se firme de nuevo.'))">
            @csrf
            <button type="submit" class="btn btn-danger">{{ 'Anular firmas y editar' }}</button>
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

<form class="document-form" method="POST" action="{{ route('contracts.return.save', $booking) }}" enctype="multipart/form-data">
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
                @if ($company['rnc']) {{ 'RNC' }}: {{ $company['rnc'] }}<br> @endif
                @if ($company['address']) {{ $company['address'] }}<br> @endif
                @if ($company['phone']) {{ $company['phone'] }} @endif
                @if ($company['email']) &middot; {{ $company['email'] }} @endif
            </div>
            <div class="contract-meta">
                <h1>{{ $title }}</h1>
                <div>{{ 'Contrato No.' }} <strong>{{ $booking->booking_number }}</strong></div>
                <div>{{ 'Fecha' }}: {{ \App\Support\Dates::date($contract->returned_at ?: now()) }}</div>
                @if ($returned)
                    <span class="badge badge-signed">{{ __('Devuelto el :date', ['date' => \App\Support\Dates::dateTime($contract->returned_at)]) }}</span>
                @else
                    <span class="badge badge-unsigned">{{ 'Revisión pendiente' }}</span>
                @endif
            </div>
        </header>

        <section>
            <div class="grid">
                <div>
                    <div class="row"><span class="label">{{ 'El arrendatario' }}</span><span class="val">{{ $booking->customer_name }}</span></div>
                    <div class="row"><span class="label">{{ 'Cédula / Pasaporte' }}</span><span class="val">{{ $contract->signer_document ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ 'Teléfono' }}</span><span class="val">{{ $booking->customer_phone ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ 'Entregado el' }}</span><span class="val">{{ \App\Support\Dates::dateTime($contract->signed_at) }}</span></div>
                </div>
                <div>
                    <div class="row"><span class="label">{{ 'Vehículo' }}</span><span class="val">{{ $bookingCar->car_name }}</span></div>
                    <div class="row"><span class="label">{{ 'Placa' }}</span><span class="val">{{ $car->license_plate ?: '—' }}</span></div>
                    <div class="row"><span class="label">{{ 'Fecha de salida' }}</span><span class="val">{{ $bookingCar->rental_start_date?->format('d/m/Y h:i A') }}</span></div>
                    <div class="row"><span class="label">{{ 'Fecha de devolución' }}</span><span class="val">{{ $bookingCar->rental_end_date?->format('d/m/Y h:i A') }}</span></div>
                </div>
            </div>
        </section>

        <fieldset @disabled($returned) style="margin: 0; padding: 0; border: 0; min-width: 0;">
            <section>
                <h2>{{ 'Comparación entrega / devolución' }}</h2>
                <table class="compare">
                    <thead>
                        <tr>
                            <th>{{ 'Concepto' }}</th>
                            <th>{{ 'Entrega' }}</th>
                            <th>{{ 'Devolución' }}</th>
                            <th>{{ 'Diferencia' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ 'Kilometraje' }}</td>
                            <td class="num">{{ $contract->pickup_mileage !== null ? number_format($contract->pickup_mileage) : '—' }}</td>
                            <td class="num"><input type="number" name="return_mileage" min="0" value="{{ $value('return_mileage') }}"></td>
                            <td>
                                @if ($driven !== null)
                                    {{ __(':distance recorridos', ['distance' => number_format($driven) . ' km']) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>{{ 'Nivel de combustible' }}</td>
                            <td class="num">{{ $fuelLabel($contract->pickup_fuel_level) }}</td>
                            <td class="num">
                                <select name="return_fuel_level">
                                    <option value="">—</option>
                                    @foreach ($fuelLevels as $key => $label)
                                        <option value="{{ $key }}" @selected($value('return_fuel_level') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                @if ($pickupFuelIndex !== false && $returnFuelIndex !== false)
                                    @if ($returnFuelIndex < $pickupFuelIndex)
                                        <span class="diff-bad">&darr; {{ $fuelLabel($contract->return_fuel_level) }} / {{ $fuelLabel($contract->pickup_fuel_level) }}</span>
                                    @else
                                        <span class="diff-ok">&check;</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>{{ 'Estado exterior' }}</td>
                            <td class="num">{{ $conditionLabel($contract->exterior_condition) }}</td>
                            <td class="num">
                                <select name="return_exterior_condition">
                                    <option value="">—</option>
                                    @foreach ($conditionLevels as $key => $label)
                                        <option value="{{ $key }}" @selected($value('return_exterior_condition') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>{{ 'Estado interior' }}</td>
                            <td class="num">{{ $conditionLabel($contract->interior_condition) }}</td>
                            <td class="num">
                                <select name="return_interior_condition">
                                    <option value="">—</option>
                                    @foreach ($conditionLevels as $key => $label)
                                        <option value="{{ $key }}" @selected($value('return_interior_condition') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section>
                <h2>{{ 'Daños a la devolución' }}</h2>
                @include('contracts.partials.vehicle-diagram', [
                    'damageTypes' => $damageTypes,
                    'marks' => (old('return_damage_marks') ? json_decode(old('return_damage_marks'), true) : null) ?: ($contract->return_damage_marks ?? []),
                    'referenceMarks' => $referenceMarks,
                    'editable' => ! $returned,
                    'inputName' => 'return_damage_marks',
                    'help' => 'En gris (E1, E2...) los daños registrados al entregar. Marca solo los daños NUEVOS encontrados a la devolución.',
                    'purpose' => 'Los daños marcados en color no estaban registrados al momento de la entrega y se consideran ocurridos durante la renta.',
                ])
                @if ($referenceMarks)
                    <ul class="reference-list">
                        <li><strong>{{ 'Daños registrados en la entrega' }}:</strong></li>
                        @foreach ($referenceMarks as $mark)
                            @continue(! isset($damageTypes[$mark['type']]))
                            <li>E{{ $loop->iteration }} &middot; {{ $damageTypes[$mark['type']]['label'] }}@if ($mark['note']) — {{ $mark['note'] }}@endif</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section>
                <h2>{{ 'Accesorios y documentos devueltos' }}</h2>
                @if ($delivered)
                    @unless ($returned)
                        <p class="diagram-help no-print">{{ 'Marca lo que el cliente devolvió. Solo aparecen los artículos que se entregaron.' }}</p>
                    @endunless
                    <div class="checklist">
                        @foreach ($delivered as $item)
                            @php $isBack = in_array($item, $returnedItems); @endphp
                            <label @class(['missing-item' => $returned && ! $isBack])>
                                <input type="checkbox" name="return_checklist[]" value="{{ $item }}" @checked($isBack)>
                                {{ $item }}
                            </label>
                        @endforeach
                    </div>
                    @if ($returned && ($missing = $contract->missingItems()))
                        <p class="missing-item" style="margin: 8px 0 0;">{{ 'Accesorios no devueltos' }}: {{ implode(', ', $missing) }}</p>
                    @endif
                @else
                    <p class="diagram-help">{{ 'No se registraron accesorios en la entrega.' }}</p>
                @endif
            </section>

            <section>
                <h2>{{ 'Fotos del vehículo a la devolución' }}</h2>
                @if ($contract->pickup_images)
                    <p class="diagram-help" style="margin-bottom: 4px;">{{ 'Fotos del vehículo' }} &mdash; {{ 'Entrega' }}:</p>
                    <div class="photos photos-ref">
                        @foreach ($contract->pickup_images as $image)
                            <div class="photo">
                                <a href="{{ \App\Support\Media::url($image) }}" target="_blank"><img src="{{ \App\Support\Media::url($image) }}" alt=""></a>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($minPhotos > 0 && ! $returned)
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
                                @unless ($returned)
                                    <label class="no-print">
                                        <input type="checkbox" name="existing_images[]" value="{{ $image }}" checked> ✓
                                    </label>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                @endif
                @unless ($returned)
                    <label class="field no-print">
                        <span>{{ 'Agregar fotos' }}</span>
                        <input type="file" name="return_images[]" accept="image/*" multiple>
                    </label>
                @endunless
            </section>

            <section>
                <h2>{{ 'Observaciones y cargos adicionales' }}</h2>
                <div class="charges-box">
                    <label class="field">
                        <span>{{ 'Cargos adicionales' }}</span>
                        <input type="number" name="return_extra_charges" min="0" step="0.01" value="{{ $value('return_extra_charges') }}">
                    </label>
                    <label class="field">
                        <span>{{ 'Motivo de los cargos (daños, combustible, retraso, limpieza...)' }}</span>
                        <input type="text" name="return_charges_description" value="{{ $value('return_charges_description') }}">
                    </label>
                </div>
                <label class="field">
                    <span>{{ 'Observaciones' }}</span>
                    <textarea name="return_notes">{{ $value('return_notes') }}</textarea>
                </label>
            </section>
        </fieldset>

        <section>
            <h2>{{ 'Firmas' }}</h2>
            <p class="declaration">{{ 'El arrendatario declara que el vehículo fue devuelto en las condiciones descritas en esta acta y acepta los daños nuevos, faltantes y cargos aquí indicados.' }}</p>

            <div class="signatures">
                <div>
                    @if ($returned)
                        <img class="sig-image" src="{{ $contract->return_customer_signature }}" alt="">
                    @else
                        <div class="sig-pad" data-target="customer_signature">
                            <canvas></canvas>
                            <div class="hint">{{ 'Firme aquí con el dedo, lápiz o mouse' }}</div>
                        </div>
                        <div class="sig-actions"><button type="button" class="btn btn-sm" data-clear="customer_signature">{{ 'Borrar firma' }}</button></div>
                    @endif
                    <div class="sig-line">
                        <strong>{{ 'Firma del arrendatario' }}</strong>
                        @if ($returned)
                            {{ $contract->return_signer_name }}
                        @else
                            <label class="field" style="margin-top: 6px; text-align: left;">
                                <span>{{ 'Nombre de quien firma' }}</span>
                                <input type="text" name="return_signer_name" value="{{ $value('return_signer_name') }}">
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @unless ($returned)
            <div class="form-actions no-print">
                <button type="submit" class="btn btn-primary" data-action="save">{{ 'Guardar sin firmar' }}</button>
                <button type="submit" class="btn btn-success" data-action="sign">{{ 'Firmar devolución y completar renta' }}</button>
            </div>
        @endunless
    </div>
</form>

@unless ($returned)
    <script>
        @include('contracts.partials.document-scripts', ['photoInputName' => 'return_images[]'])
    </script>
@endunless
</body>
</html>
