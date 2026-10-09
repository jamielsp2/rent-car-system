# Changelog

## 1.0.0 — Primera versión publicada

Versión "limpia" y reutilizable para desplegar a distintos clientes.

- Web pública: inicio, catálogo con filtros, ficha con precio automático y calendario de fechas ocupadas, reserva en línea, blog, FAQ, sobre nosotros, selector de moneda; responsive (móvil y tablet vertical).
- Panel de administración (Filament 5): vehículos con gastos y rentabilidad, reservas (resumen, calendario, hoy), contratos de entrega/devolución con firma del cliente, clientes, servicios adicionales, informes, blog, FAQ, testimonios, impuestos, monedas, usuarios y catálogos.
- Marca configurable desde el panel: nombre, logo, favicon, color, contacto, hero, "sobre nosotros", redes, copyright, moneda.
- Pagos configurables (contra entrega / transferencia) y depósito.
- Contrato editable (términos, accesorios, fotos mínimas).
- Instalación guiada: asistente `/instalar` y `php artisan rentcar:install`, con datos de ejemplo opcionales.
- Zona horaria del negocio configurable (`BUSINESS_TIMEZONE`).
- Script de empaquetado para hosting compartido (`deploy/build_package.py`) y guía de despliegue en Hostinger.
- Suite de 71 tests.
