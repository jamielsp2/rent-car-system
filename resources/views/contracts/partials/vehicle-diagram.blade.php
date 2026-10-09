@php
    /**
     * @var array $damageTypes
     * @var array $marks
     * @var bool $editable
     */
    $sidePath = 'M8,92 L8,72 Q10,60 28,56 L92,50 L128,22 Q138,14 156,14 L212,14 Q230,14 244,26 L276,50 L304,56 Q318,60 318,74 L318,92 L278,92 A28,28 0 0 0 222,92 L104,92 A28,28 0 0 0 48,92 Z';
    $endPath = 'M8,112 L8,74 Q10,62 22,58 L30,26 Q33,14 45,14 L65,14 Q77,14 80,26 L88,58 Q100,62 102,74 L102,112 Z';
@endphp

<div class="diagram" data-editable="{{ $editable ? 1 : 0 }}">
    @if ($editable)
        <p class="diagram-help no-print">{{ $help ?? 'Selecciona el tipo de daño y haz clic en el lugar exacto del vehículo. Toca una marca para quitarla.' }}</p>
    @endif

    <div class="damage-legend">
        @foreach ($damageTypes as $key => $type)
            <label class="damage-type" style="--c: {{ $type['color'] }}">
                @if ($editable)
                    <input type="radio" name="_damage_type" value="{{ $key }}" @checked($loop->first)>
                @endif
                <span class="dot">{{ $type['code'] }}</span>
                {{ $type['label'] }}
            </label>
        @endforeach
    </div>

    <svg class="vehicle-diagram" viewBox="0 0 {{ \App\Services\ContractService::DIAGRAM_WIDTH }} {{ \App\Services\ContractService::DIAGRAM_HEIGHT }}"
         xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ 'Diagrama de daños del vehículo' }}">
        <g class="car" fill="#fff" stroke="#374151" stroke-width="1.6" stroke-linejoin="round">
            {{-- Lado izquierdo (el vehículo mira a la derecha) --}}
            <g transform="translate(15,15)">
                <path d="{{ $sidePath }}"/>
                <path d="M102,50 L132,25 Q139,19 152,19 L178,19 L178,50 Z M186,19 L210,19 Q226,19 237,29 L260,50 L186,50 Z" fill="#e5e7eb"/>
                <path d="M182,50 L182,90 M8,80 L40,80 M286,80 L318,80" fill="none"/>
                <rect x="160" y="60" width="12" height="3" rx="1"/>
                <rect x="210" y="60" width="12" height="3" rx="1"/>
                <rect x="304" y="62" width="12" height="8" rx="2" fill="#fde68a"/>
                <rect x="10" y="62" width="8" height="10" rx="2" fill="#fecaca"/>
                <circle cx="76" cy="96" r="22"/><circle cx="76" cy="96" r="9" fill="#e5e7eb"/>
                <circle cx="250" cy="96" r="22"/><circle cx="250" cy="96" r="9" fill="#e5e7eb"/>
            </g>

            {{-- Lado derecho (espejado, mira a la izquierda) --}}
            <g transform="translate(685,15) scale(-1,1)">
                <path d="{{ $sidePath }}"/>
                <path d="M102,50 L132,25 Q139,19 152,19 L178,19 L178,50 Z M186,19 L210,19 Q226,19 237,29 L260,50 L186,50 Z" fill="#e5e7eb"/>
                <path d="M182,50 L182,90 M8,80 L40,80 M286,80 L318,80" fill="none"/>
                <rect x="160" y="60" width="12" height="3" rx="1"/>
                <rect x="210" y="60" width="12" height="3" rx="1"/>
                <rect x="304" y="62" width="12" height="8" rx="2" fill="#fde68a"/>
                <rect x="10" y="62" width="8" height="10" rx="2" fill="#fecaca"/>
                <circle cx="76" cy="96" r="22"/><circle cx="76" cy="96" r="9" fill="#e5e7eb"/>
                <circle cx="250" cy="96" r="22"/><circle cx="250" cy="96" r="9" fill="#e5e7eb"/>
            </g>

            {{-- Frente --}}
            <g transform="translate(40,190)">
                <rect x="14" y="110" width="16" height="12" rx="3" fill="#374151"/>
                <rect x="80" y="110" width="16" height="12" rx="3" fill="#374151"/>
                <path d="{{ $endPath }}"/>
                <path d="M34,54 L40,24 L70,24 L76,54 Z" fill="#e5e7eb"/>
                <rect x="0" y="50" width="8" height="9" rx="2"/>
                <rect x="102" y="50" width="8" height="9" rx="2"/>
                <rect x="12" y="68" width="18" height="9" rx="3" fill="#fde68a"/>
                <rect x="80" y="68" width="18" height="9" rx="3" fill="#fde68a"/>
                <rect x="38" y="72" width="34" height="13" rx="2"/>
                <path d="M8,96 L102,96" fill="none"/>
            </g>

            {{-- Vista superior (el frente apunta a la derecha) --}}
            <g transform="translate(180,180)">
                <rect x="58" y="6" width="40" height="12" rx="3" fill="#374151"/>
                <rect x="246" y="6" width="40" height="12" rx="3" fill="#374151"/>
                <rect x="58" y="132" width="40" height="12" rx="3" fill="#374151"/>
                <rect x="246" y="132" width="40" height="12" rx="3" fill="#374151"/>
                <rect x="8" y="18" width="324" height="114" rx="42"/>
                <path d="M222,30 L250,40 L250,110 L222,120 Z M84,36 L106,32 L106,118 L84,114 Z" fill="#e5e7eb"/>
                <rect x="110" y="34" width="108" height="82" rx="8" fill="none"/>
                <path d="M262,42 Q298,75 262,108 M164,34 L164,116" fill="none" stroke-dasharray="3 3"/>
                <ellipse cx="232" cy="12" rx="9" ry="5"/>
                <ellipse cx="232" cy="138" rx="9" ry="5"/>
            </g>

            {{-- Trasera --}}
            <g transform="translate(550,190)">
                <rect x="14" y="110" width="16" height="12" rx="3" fill="#374151"/>
                <rect x="80" y="110" width="16" height="12" rx="3" fill="#374151"/>
                <path d="{{ $endPath }}"/>
                <path d="M32,54 L38,26 L72,26 L78,54 Z" fill="#e5e7eb"/>
                <rect x="0" y="50" width="8" height="9" rx="2"/>
                <rect x="102" y="50" width="8" height="9" rx="2"/>
                <rect x="12" y="66" width="20" height="10" rx="3" fill="#fecaca"/>
                <rect x="78" y="66" width="20" height="10" rx="3" fill="#fecaca"/>
                <rect x="38" y="80" width="34" height="12" rx="1"/>
                <path d="M8,98 L102,98" fill="none"/>
            </g>
        </g>

        <g class="view-labels" fill="#6b7280" font-size="12" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif">
            <text x="178" y="160">{{ 'Lado izquierdo' }}</text>
            <text x="522" y="160">{{ 'Lado derecho' }}</text>
            <text x="95" y="345">{{ 'Frente' }}</text>
            <text x="350" y="345">{{ 'Vista superior' }} &rarr;</text>
            <text x="605" y="345">{{ 'Trasera' }}</text>
        </g>

        @if (! empty($referenceMarks))
            {{-- Daños registrados en la entrega: solo referencia, en gris --}}
            <g class="reference-marks" font-family="Segoe UI, Arial, sans-serif" pointer-events="none" opacity="0.75">
                @foreach ($referenceMarks as $mark)
                    @php $type = $damageTypes[$mark['type']] ?? null; @endphp
                    @continue(! $type)
                    <circle cx="{{ $mark['x'] }}" cy="{{ $mark['y'] }}" r="9" fill="#9ca3af" stroke="#fff" stroke-width="1.5"/>
                    <text x="{{ $mark['x'] }}" y="{{ $mark['y'] + 3.5 }}" fill="#fff" font-size="10" font-weight="700" text-anchor="middle">{{ $type['code'] }}</text>
                    <text x="{{ $mark['x'] + 10 }}" y="{{ $mark['y'] - 8 }}" fill="#6b7280" font-size="9" font-weight="700">E{{ $loop->iteration }}</text>
                @endforeach
            </g>
        @endif

        <g class="damage-marks" font-family="Segoe UI, Arial, sans-serif">
            @foreach ($marks as $mark)
                @php $type = $damageTypes[$mark['type']] ?? null; @endphp
                @continue(! $type)
                <g class="damage-mark" data-index="{{ $loop->index }}">
                    <circle cx="{{ $mark['x'] }}" cy="{{ $mark['y'] }}" r="9" fill="{{ $type['color'] }}" stroke="#fff" stroke-width="1.5"/>
                    <text x="{{ $mark['x'] }}" y="{{ $mark['y'] + 3.5 }}" fill="#fff" font-size="10" font-weight="700" text-anchor="middle">{{ $type['code'] }}</text>
                    <text x="{{ $mark['x'] + 10 }}" y="{{ $mark['y'] - 8 }}" fill="#111827" font-size="10" font-weight="700">{{ $loop->iteration }}</text>
                </g>
            @endforeach
        </g>
    </svg>

    <table class="damage-list">
        <tbody>
            @forelse ($marks as $mark)
                @php $type = $damageTypes[$mark['type']] ?? null; @endphp
                @continue(! $type)
                <tr>
                    <td style="width: 32px"><strong>{{ $loop->iteration }}</strong></td>
                    <td style="width: 190px"><span class="dot" style="--c: {{ $type['color'] }}">{{ $type['code'] }}</span> {{ $type['label'] }}</td>
                    <td>{{ $mark['note'] }}</td>
                </tr>
            @empty
                <tr class="damage-empty"><td colspan="3">{{ 'Sin daños marcados.' }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="diagram-purpose">{{ $purpose ?? 'Las marcas indican los daños que el vehículo ya tenía al momento de la entrega. Cualquier daño no marcado aquí que aparezca a la devolución se considerará ocurrido durante la renta.' }}</p>

    @if ($editable)
        <input type="hidden" class="diagram-marks-input" name="{{ $inputName ?? 'damage_marks' }}" value="{{ json_encode($marks) }}">
    @endif
</div>
