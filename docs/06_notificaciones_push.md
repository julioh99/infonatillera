# Módulo 06: Notificaciones Push Web y recordatorios

Avisos al navegador o teléfono del socio con **Web Push (VAPID)**, más recordatorios automáticos el día de la reunión.

## Componentes

- Controlador: [`app/Controllers/NotificacionController.php`](../app/Controllers/NotificacionController.php)
- Modelos: [`PushSubscription.php`](../app/Models/PushSubscription.php) (avisos, suscripciones, registro de envíos) y [`Recordatorio.php`](../app/Models/Recordatorio.php)
- Envío: [`core/WebPush.php`](../core/WebPush.php) (sin dependencias: JWT VAPID ES256 con openssl + cURL)
- Cliente: [`public/js/push_client.js`](../public/js/push_client.js) y service worker [`public/js/sw.js`](../public/js/sw.js)
- Vista: [`app/Views/admin/notificaciones.php`](../app/Views/admin/notificaciones.php)
- Recordatorios: [`scripts/enviar_recordatorios.php`](../scripts/enviar_recordatorios.php)
- Rutas nuevas: [`app/routes/push.php`](../app/routes/push.php)
- Migración: `scripts/migrations/20261125_1000_push_envios_recordatorios.php` (tablas `natillera_push_envios` y `natillera_recordatorios_enviados`)
- Prueba: `tests/webpush_test.php`

## Cómo funciona

1. En *Notificaciones* el usuario pulsa **Activar en mi Navegador**: se instala el service worker, el navegador pide permiso y la suscripción se guarda por POST en `/admin/notificaciones/suscribir`. Solo se aceptan endpoints de FCM, Mozilla, Apple y Windows (evita que se use el servidor para llamar a direcciones ajenas).
2. Al **emitir** una notificación (a todos o a un socio) se guarda en `natillera_notificaciones` y, después de responder al usuario, el servidor envía un **push vacío** firmado con VAPID a cada suscripción (cabeceras `Authorization: vapid t=<jwt>, k=<clave pública>` y `TTL`). El resultado de cada envío queda en `natillera_push_envios`. Si el servicio responde 404 o 410 la suscripción se borra.
3. Al recibir el push, el service worker consulta `/socio/notificaciones-pendientes-json` con la sesión del socio y muestra hasta 3 avisos (si hay más, un resumen). Si no hay sesión o falla la red muestra un aviso genérico. Al tocarla se abre/enfoca la app.
4. El botón **Enviarme una prueba** (`POST /admin/notificaciones/prueba`) manda un aviso solo a los dispositivos de quien lo pulsa.

Si el navegador no soporta push (iPhone sin instalar la app, incógnito) o el permiso está bloqueado, el cliente lo explica; los avisos siguen en la campanita y por WhatsApp.

## Llaves VAPID

Se leen primero del entorno / `.env` (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`) y, como respaldo, de `config/vapid_keys.php`.

**Importante (3.13):** las llaves de `config/vapid_keys.php` están rastreadas en git, así que deben considerarse comprometidas. Hay que **rotarlas**:

```
php scripts/generate_vapid_keys.php mailto:contacto@sudominio.com
```

El script solo imprime tres líneas: cópielas al `.env` del servidor (que no se sube al repositorio). En Windows, si openssl falla, defina antes `OPENSSL_CONF` con la ruta de un `openssl.cnf` (por ejemplo `C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\extras\ssl\openssl.cnf`). Tras rotar, los socios deben pulsar de nuevo *Activar en mi Navegador*. El archivo `config/vapid_keys.php` no se borra del repositorio; cuando haya llaves en el `.env` deja de usarse.

## Recordatorios automáticos (4.2)

El script corre por consola y decide qué enviar según la hora (zona `zona_horaria` de `config/natillera.php`), solo si hoy hay una reunión en estado `PROGRAMADA` o `EN_PROCESO`:

| Hora  | A quién | Mensaje |
|-------|---------|---------|
| 8:00  | todos los socios activos | "Hoy hay reunión…" con hora y cuota |
| 12:00 | socios que aún no tienen la cuota pagada | "Recuerda tu cuota de hoy" |
| 13:00 | socios que aún no tienen la cuota pagada | "Último recordatorio…" |

No duplica: cada (reunión, socio, franja) se guarda una sola vez en `natillera_recordatorios_enviados`, así que repetir la ejecución no reenvía. Cada recordatorio se registra como notificación personal (aparece en la campanita) y además se envía push.

Opciones: `--dry-run` (solo muestra a quién), `--ahora="YYYY-MM-DD HH:MM"` (simula la fecha y hora), `--sin-push` (solo registra, no llama a servicios push).

Prueba: `php scripts/enviar_recordatorios.php --dry-run --ahora="2026-12-05 12:00"`

Requisito: aplicar antes las migraciones (`php scripts/migrate.php`).

### Linux (cron)

`crontab -e` en el usuario del sitio (ajuste las rutas; si el servidor no está en hora de Colombia, el script igual usa `America/Bogota` internamente, pero cron dispara según la hora del servidor, por eso se fija `CRON_TZ`):

```
CRON_TZ=America/Bogota
0 8,12,13 * * * /usr/bin/php /ruta/a/infonatillera/scripts/enviar_recordatorios.php >> /ruta/a/infonatillera/logs/recordatorios.log 2>&1
```

### Windows (Programador de tareas)

Una tarea por hora (PowerShell o CMD como administrador; ajuste rutas de `php.exe` y del proyecto):

```
schtasks /Create /TN "Natillera Recordatorio 0800" /SC DAILY /ST 08:00 /TR "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe J:\www\infonatillera\scripts\enviar_recordatorios.php"
schtasks /Create /TN "Natillera Recordatorio 1200" /SC DAILY /ST 12:00 /TR "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe J:\www\infonatillera\scripts\enviar_recordatorios.php"
schtasks /Create /TN "Natillera Recordatorio 1300" /SC DAILY /ST 13:00 /TR "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe J:\www\infonatillera\scripts\enviar_recordatorios.php"
```

El equipo debe estar encendido a esa hora y en hora de Colombia (o use `--ahora` solo para pruebas). El script lee la BD del `.env` del proyecto.

## Pruebas

```
php tests/webpush_test.php
```

Usa llaves fijas de prueba (no las del proyecto, no requiere `openssl.cnf`), verifica base64url sin relleno, la conversión DER→raw (r||s de 64 bytes), el JWT con `openssl_verify`, y levanta un `php -S` local (puerto 8115) que registra la petición completa y responde 201, 404, 410 y 500 para comprobar cabeceras, cuerpo vacío y el manejo de suscripciones caducadas. No contacta servicios push reales.

[Anterior: Programación Reuniones](./05_programacion_reuniones.md) | [Índice](./README.md) | [Siguiente: Gestión de Socios](./07_gestion_socios.md)
