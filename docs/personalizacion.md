# Personalización por cliente

Todo se hace desde `/admin` → **Configuración**; no hay que tocar código ni la base de datos.

## Web pública (*Configuración → Web pública*)

- **Marca**: nombre, logo, favicon y color principal (`site_brand_color`, botones y detalles; el tono oscuro se calcula solo).
- **Contacto**: teléfono, WhatsApp, correo, dirección y horario. Lo que dejes vacío no se muestra (ni el botón flotante de WhatsApp).
- **Portada (hero)**: imagen, etiqueta, título y subtítulo.
- **Sobre nosotros**: título, texto y tres cifras destacadas (con imágenes).
- **Redes**: Facebook e Instagram. **Copyright** del pie. **Moneda por defecto**.

Los valores iniciales salen de `config/site.php`; lo guardado en el panel (tabla `settings`, claves `site_*`) tiene prioridad.

## Pagos y depósito (*Pagos y depósito*)

- Activa **pago contra entrega** y/o **transferencia bancaria** con su descripción (datos de la cuenta). Debe haber al menos un método.
- **Depósito** en garantía (`rentcar_deposit_amount`); cada vehículo puede tener el suyo.

## Contrato de renta (*Contrato de renta*)

Título, RNC/identificación de la empresa, representante, términos y condiciones, accesorios a verificar, fotos mínimas del vehículo y nota al pie. Los datos de la empresa se toman de *Web pública*.

> El texto de ejemplo está redactado para **República Dominicana** (menciones a RNC, ITBIS, leyes locales). Un abogado del cliente debe revisarlo y adaptarlo a su país antes de usarlo; revisa también la ortografía de las cláusulas 3, 4 y 10.

## Monedas e impuestos

- *Monedas*: USD es la moneda base (los precios se guardan en ella); añade o ajusta tasas de DOP, EUR u otras. El visitante cambia de moneda en la web.
- *Catálogos → Impuestos*: todos los impuestos publicados se aplican al precio (por defecto ITBIS 18 %). Desactívalo o cámbialo según el país.

## Catálogos y contenido

Marcas, tipos, transmisiones, combustibles, colores, servicios adicionales, FAQ, testimonios, blog y usuarios tienen su propio apartado. Los vehículos se crean en *Vehículos → Nuevo*.

## Cambios que sí requieren código

- Idioma distinto del español (textos en vistas y recursos de Filament).
- Cambios de diseño más allá del color (vistas en `resources/views/site` y `resources/views/components/site`).
- Panel: tema en `resources/css/filament/admin/theme.css`.

## Generar textos con IA (Groq)

En **Vehículos → Nuevo/Editar → Descripción en la web**, los botones **Generar con IA** escriben el *Resumen* y la *Descripción completa* a partir de los datos ya llenados arriba (nombre, marca, año, tipo, transmisión, combustible, asientos, puertas, kilometraje, tarifa). Si ya hay texto, pide confirmación antes de reemplazarlo. Revisa siempre el resultado antes de guardar.

Requiere una clave de [Groq](https://console.groq.com) en el `.env` del sitio (sin la clave, los botones no aparecen):

```
GROQ_API_KEY=gsk_...
# GROQ_MODEL=openai/gpt-oss-120b
```

No subas la clave a git. En Hostinger, edítala en el `.env` del sitio y recarga la página.
