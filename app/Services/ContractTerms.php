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

    public function render(Booking $booking, ?string $signerDocument = null): string
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

        $terms = $this->settings->terms();

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
