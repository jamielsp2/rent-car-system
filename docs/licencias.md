# Licencias

Las licencias las creas tú en la **plataforma de licencias** (carpeta `license-server/`, aparte de este proyecto) y el cliente pega el código en `https://su-dominio/licencia`.

**La licencia no bloquea el sistema.** Si falta, está suspendida, fue borrada o venció, el panel de administración muestra un aviso fijo —*«Este tenant no está activado. Activa tu licencia…»*— con un botón que lleva a `/licencia`. La web pública y el panel siguen funcionando. El aviso solo se ve en el panel, no en la web pública.

## Página «Licencia» del panel

En el panel de administración, menú **Configuración → Licencia** (`/admin/licencia`):

- **Estado:** si está activa, vencida o suspendida; cliente, tipo (mensual/anual), fecha de vencimiento y días restantes, dominio, código de licencia, hora de la **última consulta** y dónde se guardan los archivos (R2 o este servidor).
- **Comprobar ahora:** consulta a la plataforma en ese momento (renovaciones, suspensiones).
- **Cambiar la licencia:** pega un código nuevo y pulsa *Activar licencia*. Reemplaza al actual; si el código no es válido, se rechaza y se conserva el anterior.

El menú muestra un **«!» rojo** junto a *Licencia* mientras haya un aviso rojo. El botón «Ver licencia» del aviso lleva a esta página.

## Cómo funciona

1. En la plataforma: **Nueva licencia** → cliente, tipo (**mensual** o **anual**), dominio opcional → se genera un código largo que empieza por `RCS1.`.
2. Se instala el sistema del cliente (`/instalar`). Hasta que se active la licencia, el panel muestra el aviso rojo.
3. El cliente (o tú) abre `/licencia`, pega el código y pulsa **Activar licencia**. El aviso desaparece.

El código está **firmado** (Ed25519). El sistema solo lleva la clave pública (`config/license.php`), así que puede comprobar que el código es auténtico sin internet, pero nadie puede fabricar uno sin tu clave privada, que vive solo en la plataforma. Si alguien cambia la fecha dentro del código, la firma deja de coincidir.

## Qué avisa el panel

| Situación | Aviso |
|---|---|
| Sin licencia, código inválido, licencia de otro dominio | **Rojo**: tenant no activado |
| Revocada o borrada en la plataforma | **Rojo**: suspendida o ya no existe |
| Vencida hace más de 3 días (`grace_days`) | **Rojo**: venció, renovar y activar |
| Vencida hace 3 días o menos | Amarillo |
| Vence en 7 días o menos | Amarillo con la cuenta atrás |
| Vigente | Sin aviso |

### Renovar
En la plataforma, **Renovar** suma un periodo (1 mes o 1 año; si aún no ha vencido, se suma a la fecha actual de vencimiento). **El sistema del cliente lo recibe solo**: cada 30 minutos (`check_minutes`) consulta a la plataforma y actualiza la fecha; mientras la licencia no esté vigente lo intenta cada 2 minutos. También puede pulsar **Comprobar ahora** en `/licencia`. Si el cliente no tiene salida a internet, pega el código nuevo.

### Suspender o borrar
**Revocar** o **Borrar** en la plataforma: en la siguiente consulta (máximo 30 minutos) el panel del cliente muestra el aviso rojo. **Reactivar** lo revierte. Una licencia que la plataforma ya no conoce cuenta como revocada. Si la plataforma no responde, no cambia nada: el aviso solo aparece cuando la plataforma contesta.

La consulta se hace **después de enviar la respuesta**, así que nadie espera por ella.

### Dominio
Si pones un dominio al crear la licencia, solo es válida en ese dominio y sus subdominios. Vacío = cualquier dominio.

## Dónde se guarda
`storage/app/license.key` (el código) y `storage/app/license-state.json` (última consulta). No dependen de la base de datos y no se suben a git.

## Pruebas y desarrollo
Las pruebas automáticas se saltan la licencia. En local aparece el aviso hasta que actives una licencia creada en tu plataforma, en `http://127.0.0.1:8000/licencia`.

## Límites (honestamente)
Con este modelo la licencia **avisa, no obliga**: un cliente puede ignorar el aviso y seguir usando el sistema, y con acceso al código podría quitarlo. Sirve para recordar y para que el cliente sepa a quién pagar, no para impedir el uso. Si algún día quieres obligar, el punto de entrada es `App\Http\Middleware\EnsureLicensed`: ahí se puede redirigir a `/licencia` cuando `License::allows()` sea falso.

## Cambiar la clave de firma
Si pierdes o expones la clave privada: genera un par nuevo (borra `license-server/config.php` y repite la configuración inicial), pon la nueva clave pública en `config/license.php`, publica una versión y emite códigos nuevos. Los códigos anteriores dejan de valer.
