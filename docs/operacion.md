# Operación diaria

## Ciclo de una reserva

| Estado | Significado |
|---|---|
| Pendiente | El cliente reservó en la web (o se creó en el panel); falta confirmar |
| Confirmada | El negocio la aceptó; el vehículo queda bloqueado en esas fechas |
| Entregada | Se firmó el contrato de entrega; el vehículo está con el cliente |
| Completada | Se registró la devolución |
| Cancelada | Liberó las fechas |

- Firmar el **contrato de entrega** pasa la reserva a *Entregada*; anular ese contrato la devuelve a *Confirmada*.
- El sistema impide reservar fechas solapadas para un mismo vehículo, tanto en la web como en el panel.
- Las fechas usan la zona horaria del negocio (variable `BUSINESS_TIMEZONE` del `.env`, `America/Santo_Domingo` por defecto; cámbiala para otros países, p. ej. `America/Mexico_City`).

## Módulo Reservas (`/admin/reservas`)

- **Resumen**: tarjetas y gráficos de reservas (tendencia, vehículos más rentados, estados).
- **Calendario**: vista mensual/semanal/lista de todas las reservas; clic para abrirlas.
- **Hoy**: salidas y devoluciones del día con acceso directo al contrato correspondiente.
- **Todas las reservas**: tabla con filtros, y edición con cálculo automático de totales (impuestos, servicios, depósito).

## Contratos

1. En la reserva (o en *Hoy*) abre **Contrato de entrega**: verifica accesorios, anota combustible/kilometraje, adjunta las fotos mínimas del vehículo y deja que el **cliente firme** en pantalla (ideal en tablet).
2. Se genera un documento imprimible/guardable en PDF desde el navegador.
3. Al volver el vehículo, abre **Contrato de devolución** con la inspección y la firma.
4. Un contrato firmado se puede **anular** (queda registro) y volver a firmar.

El texto legal se edita en *Configuración → Contrato de renta*.

## Vehículos y rentabilidad

En cada vehículo registra **gastos** (mantenimiento, lavado, gomas…). La ficha muestra ingresos, gastos, rentabilidad y ocupación del año.

## Informes (`/admin/informes`)

Panel con ingresos, gastos, rentabilidad por vehículo, ocupación, clientes y reservas por estado.

## Respaldo

Haz copia de: (1) la base de datos (phpMyAdmin → Exportar) y (2) la carpeta `public/media`. Hazlo antes de cada actualización.
