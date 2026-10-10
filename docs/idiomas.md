# Idiomas (español e inglés) y SEO

La web pública viene en **español** (idioma base) y **English**. El panel de administración y los contratos siguen en español.

## Cómo se ve para el visitante

- Español en la raíz (`/cars/toyota-rav4`) e inglés bajo `/en` (`/en/cars/toyota-rav4`). Cada idioma tiene su propia dirección, que es lo que Google necesita para indexar los dos.
- En la barra superior hay un selector **ES | EN**. Lleva a la **misma página** en el otro idioma (con sus filtros y fechas), no a la portada.
- Fechas, meses y formatos de número cambian con el idioma; los precios siguen el selector de moneda.

## Activar o quitar idiomas

**Configuración → Web pública → Idiomas.** Con un solo idioma activo desaparece el selector, no se generan `hreflang` y `/en` responde 404. El español siempre está activo.

## Textos fijos de la web

Los textos de los botones, menús y formularios están en `lang/en.json` (clave = el texto en español). Para cambiar una traducción edita ese archivo. Los textos que salen de `config/site.php` (portada, "Sobre nosotros", "¿Por qué elegirnos?") también se traducen ahí si el cliente no los cambió.

## Contenido que escribe el cliente

Cada formulario del panel tiene una sección **Versión en inglés (English)**, cerrada por defecto:

| Dónde | Campos en inglés |
|---|---|
| Vehículos | nombre, resumen, descripción completa |
| Blog | título, resumen, contenido |
| Preguntas frecuentes | pregunta, respuesta |
| Servicios adicionales | nombre, descripción corta |
| Testimonios | testimonio, cargo o empresa |
| Marcas, tipos, transmisiones, combustibles, colores, impuestos | nombre |
| Configuración → Web pública | etiqueta, título y subtítulo de la portada, horario, "Sobre nosotros", derechos |

Un campo en inglés vacío muestra el texto en español. Se guarda en la columna `translations` (JSON) de cada tabla; las columnas originales siguen siendo el español.

### Con IA (Groq)

Con `GROQ_API_KEY` en el `.env` (ver [personalizacion.md](personalizacion.md)):

- **Generar con IA** en *Resumen* y *Descripción completa* de un vehículo escribe el texto en **español y en inglés** a la vez. El inglés no es una traducción literal: se redacta como lo haría un copywriter nativo, con los mismos datos.
- **Traducir desde español** (cabecera de cada sección English) traduce los campos que ya escribiste en español.
- Siempre se muestra como borrador: revisa y guarda.

## SEO

Cada página, en cada idioma, declara:

- `<title>` y meta description propios (60 y 155 caracteres de referencia).
- `<link rel="canonical">` limpio (sin filtros ni fechas) y `hreflang` `es`, `en` y `x-default`.
- Open Graph y Twitter Card (con `og:locale` y `og:locale:alternate`).
- Datos estructurados (JSON-LD) en el idioma de la página: `AutoRental` (inicio y sobre nosotros), `Product`/`Car` con `Offer` (ficha del vehículo), `FAQPage`, `BlogPosting` y `BreadcrumbList`.
- `noindex`: reservas, confirmaciones y catálogos con filtros.

**Título y descripción para Google.** En vehículos y blog, la sección **Cómo aparece en Google** permite escribir un título y una descripción por idioma (con contador de caracteres). Vacíos, se arman solos: *nombre – Alquiler de vehículos* y el resumen. En vehículos, **Generar con IA** los redacta en ambos idiomas.

**Páginas sin traducir.** Una ficha o artículo sin texto en inglés se muestra en español dentro de `/en`, pero con `noindex` y sin `hreflang` hasta que se traduzca, para que Google no indexe contenido duplicado. Tampoco aparece en el sitemap en inglés.

### Sitemap y robots

- `/sitemap` (y `/sitemap.xml`) lista todas las páginas en ambos idiomas con sus alternativas `hreflang`. En hosting compartido un `.xml` que no existe nunca llega a PHP, por eso `robots.txt` apunta a `/sitemap`.
- `robots.txt` es un archivo real (en la raíz del proyecto y en `public/`) que bloquea `/admin`, `/instalar`, `/licencia`, las reservas y el cambio de moneda. Se **regenera con la dirección del sitio** al instalar y al guardar *Web pública*.
- Después de publicar: en Google Search Console añade la propiedad y envía `https://tu-dominio/sitemap`.

## Cómo añadir otro idioma

1. Añade el código a `App\Support\Lang::SUPPORTED` y `REGIONS`.
2. Crea `lang/<código>.json` con las traducciones.
3. Amplía `App\Filament\Support\TranslationSection` (hoy solo ofrece la sección English) para que también muestre el nuevo idioma.
4. Rutas (`/xx`), selector, `hreflang` y sitemap ya recorren `Lang::enabled()`; añade el idioma al grupo de rutas en `routes/web.php`.
