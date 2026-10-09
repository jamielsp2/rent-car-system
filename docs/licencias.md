# Licencias

El sistema solo funciona con una licencia vigente. Las licencias las creas tú en la **plataforma de licencias** (carpeta `license-server/`, aparte de este proyecto) y el cliente pega el código en `https://su-dominio/licencia`.

## Cómo funciona

1. En la plataforma: **Nueva licencia** → cliente, tipo (**mensual** o **anual**), dominio opcional → se genera un código largo que empieza por `RCS1.`.
2. Se instala el sistema del cliente (`/instalar`). Al terminar, todo lleva a `/licencia`.
3. El cliente (o tú) pega el código y pulsa **Activar licencia**. Listo.

El código está **firmado** (Ed25519). El sistema solo lleva la clave pública (`config/license.php`), así que puede comprobar que el código es auténtico sin internet, pero nadie puede fabricar uno sin tu clave privada, que vive solo en la plataforma. Si alguien cambia la fecha dentro del código, la firma deja de coincidir.

### Vencimiento
- Al vencer hay **3 días de gracia** (`grace_days`): el sistema sigue y el panel avisa. Pasados, todo (web pública y panel) lleva a `/licencia`.
- Desde 7 días antes de vencer, el panel muestra un aviso con el enlace a la licencia.

### Renovar
En la plataforma, **Renovar** suma un periodo (1 mes o 1 año; si aún no ha vencido, se suma a la fecha actual de vencimiento). **El sistema del cliente lo recibe solo**: cada 12 horas (`check_hours`) consulta a la plataforma y actualiza la fecha; si ya venció, lo intenta cada 10 minutos. También puede pulsar **Comprobar ahora** en `/licencia`. Si el cliente no tiene salida a internet, pega el código nuevo.

### Suspender
**Revocar** en la plataforma: el sistema del cliente se detiene en cuanto consulte (máximo 12 horas). **Reactivar** lo revierte. Si la plataforma no responde, el sistema **no se bloquea**: la licencia sigue valiendo hasta su fecha.

### Dominio
Si pones un dominio al crear la licencia, solo funciona en ese dominio y sus subdominios. Vacío = cualquier dominio.

## Dónde se guarda
`storage/app/license.key` (el código) y `storage/app/license-state.json` (última consulta). No dependen de la base de datos y no se suben a git.

## Pruebas y desarrollo
Las pruebas automáticas se saltan la licencia. Para trabajar en local necesitas una licencia como cualquier cliente: créala en tu plataforma y pégala en `http://127.0.0.1:8000/licencia`.

## Límites (honestamente)
El código del sistema está en manos del cliente: alguien con conocimientos podría quitar la comprobación del código. La licencia protege frente al uso normal, a las copias y a los clientes que dejan de pagar, no frente a un desarrollador decidido. Para más protección: alojar tú el sistema, ofuscar el código o añadir comprobaciones en más puntos.

## Cambiar la clave de firma
Si pierdes o expones la clave privada: genera un par nuevo (borra `license-server/config.php` y repite la configuración inicial), pon la nueva clave pública en `config/license.php`, publica una versión y emite códigos nuevos. Los códigos anteriores dejan de valer.
