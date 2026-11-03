<?php

namespace App\Services;

use App\Models\Booking;
use App\Support\Money;

/**
 * Renders the contract clauses, replacing {variables} with the rental's data.
 */
class ContractTerms
{
    public function __construct(protected ContractSettings $settings)
    {
    }

    public static function variables(): array
    {
        return [
            'company_name' => 'Nombre de la empresa',
            'company_rnc' => 'RNC de la empresa',
            'company_address' => 'Dirección de la empresa',
            'company_phone' => 'Teléfono de la empresa',
            'customer_name' => 'Nombre del cliente',
            'customer_document' => 'Cédula / pasaporte del cliente',
            'customer_phone' => 'Teléfono del cliente',
            'customer_email' => 'Correo del cliente',
            'customer_license' => 'Licencia de conducir del cliente',
            'vehicle' => 'Nombre del vehículo',
            'plate' => 'Placa',
            'vin' => 'Chasis (VIN)',
            'booking_number' => 'Número de reserva',
            'start_date' => 'Fecha de salida',
            'end_date' => 'Fecha de devolución',
            'total_amount' => 'Monto total',
            'deposit_amount' => 'Depósito de garantía',
            'today' => 'Fecha de hoy',
        ];
    }

    public function render(Booking $booking, ?string $signerDocument = null, string $language = 'es'): string
    {
        $company = $this->settings->company();
        $rental = $booking->rental;
        $car = $rental?->car;

        $values = [
            'company_name' => $company['name'],
            'company_rnc' => $company['rnc'],
            'company_address' => $company['address'],
            'company_phone' => $company['phone'],
            'customer_name' => $booking->customer_name,
            'customer_document' => $signerDocument,
            'customer_phone' => $booking->customer_phone,
            'customer_email' => $booking->customer_email,
            'customer_license' => $booking->customer?->license_number,
            'vehicle' => $rental?->car_name,
            'plate' => $car?->license_plate,
            'vin' => $car?->vin,
            'booking_number' => $booking->booking_number,
            'start_date' => $rental?->rental_start_date?->format('d/m/Y h:i A'),
            'end_date' => $rental?->rental_end_date?->format('d/m/Y h:i A'),
            'total_amount' => Money::format($booking->amount),
            'deposit_amount' => Money::format($booking->deposit_amount ?: 0),
            'today' => now()->format('d/m/Y'),
        ];

        $terms = $this->settings->terms($language);

        foreach ($values as $key => $value) {
            $value = $value === null || $value === '' ? '<span class="blank-line"></span>' : e($value);
            $terms = str_replace(['{' . $key . '}', '{{ ' . $key . ' }}', '{{' . $key . '}}'], $value, $terms);
        }

        return static::clean($terms);
    }

    /**
     * Clauses are written by staff in the settings editor; keep formatting
     * tags only and drop scripts and inline event handlers.
     */
    public static function clean(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><span><div><table><tr><td><th><tbody><thead>');

        return preg_replace('/\s(on\w+|style|href|src)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    }

    /**
     * English version of DEFAULT. Used when the contract is signed in English and the
     * site has no English clauses saved. Review with a lawyer before relying on it.
     */
    public const DEFAULT_EN = <<<'HTML'
<p><strong>1.- DEFINITIONS.</strong><br>The lease grants the rental of the vehicle described in part two of this rental contract and all the terms and conditions set out here. The lessee is the person (individual or legal entity) whose name appears in part two. The lessor is V&amp;D RENT A CAR, a commercial company incorporated under the laws of the Dominican Republic. The term vehicle includes all its tires (including the spare), tools, equipment, accessories and vehicle documents. Neither this contract nor the vehicle leased may be assigned or transferred by the lessee.</p>
<p><strong>2.- DATE AND PLACE OF RETURN OF THE VEHICLE.</strong><br>The lessee shall return the rented vehicle to the V&amp;D RENT A CAR offices on the date set in part two of the contract. Under no circumstances may this period exceed 30 days. The lessee must return the vehicle before the date set.</p>
<p><strong>3.- AUTHORIZATION TO DRIVE THE VEHICLE.</strong><br>The only person authorized to drive the vehicle is the lessee described in part two of the contract and any other authorized driver whose driver's license and signature appear in part two (original copy) of this contract. Neither the lessee nor any additional authorized driver shall be considered an agent, servant or employee of the lessor for any purpose. An additional authorized driver may incur a fee. If the lessee is a legal entity, its employees or executives who are at least 25 years old and hold a valid driver's license are considered authorized drivers.</p>
<p><strong>4.- PROHIBITED USES OF THE VEHICLE.</strong><br>Pushing or towing any object or trailer. Training, speed tests or racing. Any illegal purpose or purpose penalized by the laws of the Dominican Republic. Crossing the border with Haiti. Driving in a reckless, abusive or illegal manner. Carrying explosive substances. Using it on unpaved roads or tracks. Use that causes damage to the vehicle (warning light on, a flat tire, abnormal vibrations, etc.), including a flat tire, engine misalignment or abnormal noises. Carrying more passengers than the vehicle registration allows. Loading more weight than established. Use by anyone who has given the lessor false or misleading information.</p>
<p><strong>5.- CONDITION OF THE VEHICLE.</strong><br>The lessee accepts that the vehicle is rented "as is", in its current condition at the time of rental, and that the lessor gives no express or implied warranty that the vehicle is suitable for the lessee's needs. The lessee confirms having received it in the same condition and shall not allow repairs or maintenance without the express consent of the lessor, failing which the lessee shall be fully liable for the costs.</p>
<p><strong>6.- CHARGES AND COSTS.</strong><br>The lessee agrees to pay for the full time and mileage recorded at the fixed rate on page two of the contract. Mileage is established by reading the odometer installed by the manufacturer. The lessee undertakes not to tamper with the odometer; if it does, the lessee shall pay the cost of the repair plus 500 kilometers per rental day at the established rate.</p>
<p>The lessee agrees to pay the cost of fuel if the vehicle is returned with less fuel than when it was rented, plus a service charge at the current rate. The lessee shall pay all taxes and any other surcharges. A surcharge is not a tax, nor is the partial damage liability waiver cost, if accepted when the relevant columns were completed. The lessee shall pay all fines, penalties, legal costs and other expenses related to the use of the vehicle while it is in the lessee's possession or that of another driver.</p>
<p>The charges shown on page two are subject to final audit. The lessor is authorized to correct or debit any charge to the lessee's credit card, if any.</p>
<p><strong>7.- LIABILITY FOR DAMAGES.</strong><br>The lessee shall report any loss, theft or damage to the vehicle immediately upon learning of it, by calling the office where the vehicle was rented, formally notify the police and complete an accident report at the lessor's office. The lessee shall gather the relevant information about the other driver and vehicle, regardless of fault, and shall notify the lessor of any notice received that is connected in any way with the accident, and shall fully cooperate with the lessor and its insurance company.</p>
<p><strong>8.- DAMAGE LIABILITY WAIVER.</strong><br>The lessor will offer the lessee the option of a damage liability waiver for the rented vehicle, which reduces the lessee's liability to a deductible stated on page two, in exchange for a daily or weekly amount stated on the same page. This waiver is optional; it is not insurance, but a contractual clause. If the lessee has breached this contract or the applicable laws and regulations, the waiver is void and the lessee assumes full liability for the damage and losses caused.</p>
<p><strong>9.- LIABILITY TO THIRD PARTIES.</strong><br>The rented vehicle is covered by an insurance policy that covers property damage and third-party damage while it is under this rental contract and the lessee has not breached the law or the terms of the contract. The policy limits are the minimum limits established by the mandatory motor vehicle insurance law. A copy of the policy is available for inspection at the lessor's office. However, any liability above what the policy provides is at the lessee's own risk and expense.</p>
<p><strong>10.- PAYMENTS.</strong><br>Upon request, the lessee shall pay the lessor all charges, fees and expenses, including payment for or loss of damage to the vehicle and attorney's fees. Any unpaid balance is subject to the maximum annual interest rate permitted, computed monthly. This contract is the only agreement between the parties, and any valid change must be signed by the lessor.</p>
<p>Santo Domingo, D. N., Dominican Republic.</p>
HTML;

    public const DEFAULT = <<<'HTML'
<p><strong>1.- DEFINICIONES.</strong><br>El arrendamiento acepta el alquiler del vehículo descrito en la parte número dos del presente contrato de arrendamiento y todos los términos y condiciones aquí descritos. El arrendatario es la persona (física o moral) cuyo nombre aparece en la parte número dos. El arrendador es V&amp;D RENT A CAR, sociedad comercial constituida bajo las leyes de la República Dominicana. El término vehículos incluye, todas sus gomas (incluyendo repuesto), herramientas, equipos, accesorios y documentos del vehículo. Este contrato ni el vehículo objeto del arrendamiento son asignables ni transferibles por parte del arrendatario.</p>
<p><strong>2.- FECHA Y LUGAR DE DEVOLUCIÓN DEL VEHÍCULO.</strong><br>El vehículo arrendado será devuelto por el arrendatario en las oficinas de V&amp;D RENT A CAR, en la fecha establecida en la parte número dos del contrato. Bajo ninguna circunstancia este período será mayor de 30 días. El arrendatario deberá devolver el vehículo antes de la fecha establecida.</p>
<p><strong>3.- AUTORIZACIÓN A CONDUCIR VEHÍCULO.</strong><br>La única persona autorizada a conducir el vehículo lo es la persona descrita como arrendatario en la parte dos del contrato y cualquier otro conductor autorizado, cuya licencia de conducir y firma aparezcan en la parte dos (copia original) del presente contrato. De ninguna manera, el arrendatario o el conductor adicional autorizado será considerado un agente sirviente o empleado del arrendador para ningún efecto o propósito. Puede haber un cargo por cada conductor adicional autorizado. En caso de que el arrendatario sea una persona moral, serán considerados conductores autorizados los empleados o ejecutivos de ella que estén en el desempeño 25 años y que estén en posesión de una licencia de conducir vigente.</p>
<p><strong>4.- PROHIBICIÓN EN EL USO DEL VEHÍCULO.</strong><br>Para empujar o halar ningún objeto o tráiler. Para entrenar, pruebas de velocidad o carreras. Para ningún propósito ilegal o penalizado por las leyes de República Dominicana. Para cruzar la frontera con Haití. Conducir de una manera desconsiderada, abusiva o de manera ilegal. Para cargar sustancias explosivas. Para utilizarlo en carreteras o caminos sin asfaltar. Cuando el uso adicional del vehículo le causara daños a este (luz de advertencia encendida, una goma vacía, vibraciones anormales, etc.) una goma vacía, un desnivelado del motor, ruidos anormales, etc. Cargar más pasajeros que los que establece la matrícula del vehículo. Poner en el vehículo mayor carga que la establecida. Por parte de cualquiera que haya dado al arrendador información falsa o engañosa.</p>
<p><strong>5.- CONDICIONES DEL VEHÍCULO.</strong><br>El arrendatario acepta que está alquilando el vehículo “como está” y su condición actual, al momento del arrendamiento y que el arrendador no da ninguna garantía expresa o implícita de lo adecuado del vehículo para las necesidades del arrendatario. El arrendatario da fe de haber recibido en las mismas condiciones, no permitiendo que se le hagan reparaciones o mantenimiento sin el consentimiento expreso del arrendador o de lo contrario será responsable del pago total de los costos.</p>
<p><strong>6.- CARGOS Y COSTOS.</strong><br>El arrendatario acepta pagar la totalidad del tiempo y kilometraje computarizado a la tarifa fija en la página número dos del contrato. El kilometraje será establecido al leer el podómetro instalado por el fabricante. El arrendatario se compromete a no violar el podómetro, si así lo hiciere deberá pagar el costo de la reparación más la cantidad de 500 kilómetros por día de alquiler a la tarifa establecida.</p>
<p>El arrendatario acepta pagar el costo del combustible, en caso de que trajera el vehículo con menos que cuando lo rentó, más un cargo por servicio a la tarifa vigente. El arrendatario pagará todos los impuestos y cualquier otro recargo. Un recargo no es un impuesto, así como el costo del relevo de responsabilidad parcial por daños, si lo hubiese aceptado al iniciar las columnas destinadas al efecto. El arrendatario pagará todas las multas, penalidades, costos legales y otros gastos relacionados al uso del vehículo mientras este estuviese en su posesión, o en la de otro conductor.</p>
<p>Los cargos que se muestran en la página dos están sujetos a auditoría final. El arrendatario está autorizado a corregir o debitar cualquier cargo en la tarjeta de crédito del arrendatario, si la hubiera.</p>
<p><strong>7.- RESPONSABILIDADES POR DAÑOS.</strong><br>El arrendatario reportará la pérdida, hurto o robos y daños al vehículo inmediatamente conozca de su ocurrencia, llamando a la oficina donde se rentó el vehículo, notificará formalmente a la policía y completará un reporte de accidente en la oficina del arrendador. Recabará la información pertinente relativa al otro conductor y el vehículo, sin importar de quién sea la culpa y notificará asimismo de cualquier notificación que recibiera conectada de alguna manera al accidente y aceptará cooperar plenamente con el arrendador y su compañía aseguradora.</p>
<p><strong>8.- RELEVO DE RESPONSABILIDADES POR DAÑOS.</strong><br>El arrendador ofrecerá la opción al arrendatario de ofrecerle un relevo de responsabilidad por daños al vehículo arrendado, el cual reduce la responsabilidad del arrendatario a un deducible que estará establecido en la página dos, a cambio de una suma diaria o semanal establecida en la misma página. Este relevo es opcional, no es seguro, sino una cláusula contractual. En caso de que el arrendatario hubiere violado el presente contrato o las leyes y disposiciones vigentes, el relevo queda sin efecto, asumiendo el arrendatario la responsabilidad total por los daños y pérdidas ocasionadas.</p>
<p><strong>9.- RESPONSABILIDAD CIVIL.</strong><br>El vehículo arrendado está cubierto por una póliza de seguros que cubre daños a la propiedad y daños a terceros, mientras esté bajo este contrato de alquiler y el arrendatario no haya violado las leyes ni los términos de contrato. Los límites de la póliza son los límites mínimos establecidos por la ley obligatoria de seguros de vehículos de motor. Copia de dicha póliza está disponible para inspección en la oficina del arrendador. Sin embargo, cualquier responsabilidad por encima de la ofrecida por dicha póliza corre por cuenta y riesgo del arrendatario.</p>
<p><strong>10.- COBROS.</strong><br>El arrendatario pagará al arrendador, a su solicitud, todos los cargos, honorarios y gastos, incluyendo pago o pérdida por daños al vehículo y honorarios de abogado. Cualquier balance no pagado estará sujeto a la tasa máxima anual de interés permitida, computada mensualmente. Este contrato es el único que las partes y cualquier cambio válido deberá estar firmado por el arrendador.</p>
<p>Santo Domingo, D. N., República Dominicana.</p>
HTML;
}
