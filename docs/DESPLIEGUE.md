# Guía de despliegue de InfoNatillera

Pasos para subir el sistema a un servidor Apache + PHP + MySQL. Siga el orden. Los comandos `php ...` se escriben en la terminal (SSH o consola del hosting) estando en la carpeta del proyecto.

Requisitos del servidor: PHP 7.2 o superior con las extensiones `pdo_mysql`, `openssl`, `mbstring`, `json` y `curl`; MySQL 5.7+ / MariaDB 10.3+; Apache con `mod_rewrite` y `AllowOverride All`.

## 1. Respaldo previo (antes de tocar nada)

1. Base de datos:
   ```
   mysqldump -h SERVIDOR -u USUARIO -p --single-transaction --routines NOMBRE_BD > respaldo_AAAA-MM-DD.sql
   ```
   (o "Exportar" en phpMyAdmin, formato SQL). Guarde el archivo fuera de la carpeta del sitio.
2. Archivos subidos (firmas y evidencias): copie la carpeta `public/uploads/` completa (zip o `cp -a`).
3. Código actual del servidor, si ya había uno: copie la carpeta completa del sitio y su `.env`.
4. Compruebe que el archivo `.sql` pesa algo razonable y que puede abrirse.

## 2. Qué copiar al servidor

Copie TODO el proyecto excepto:

| No copiar | Motivo |
|---|---|
| `.env` | Se crea aparte en el servidor (paso 3); contiene secretos |
| `.git/` | Historial; no es necesario |
| `scratch/`, `export/`, `node_modules/`, `package.json` | Herramientas locales |
| `tests/` | Opcional: solo sirve para pruebas |
| `storage/` con datos locales | Allí quedan copias del histórico del servidor; no se pisan |
| `public/uploads/` con datos locales | No pise las firmas y fotos reales del servidor |
| `natillera2.sqlite`, `seed.php`, `info.md`, `resumen.md`, `task.md` | Material de desarrollo |

Si es una actualización, copie encima conservando `.env`, `public/uploads/` y `storage/`.

## 3. Crear o actualizar el `.env` en el servidor

Copie `.env.example` a `.env` (en la raíz del proyecto, junto a `app/` y `config/`) y complete los valores. Nunca suba `.env` a git ni a carpetas públicas.

| Clave | Qué significa / de dónde sale |
|---|---|
| `DB_DRIVER` | Siempre `mysql` |
| `DB_HOST` | Servidor MySQL (`127.0.0.1` o `localhost` si es el mismo servidor; si no, el dato del hosting) |
| `DB_PORT` | Puerto, normalmente `3306` |
| `DB_DATABASE` | Nombre de la base (panel del hosting) |
| `DB_USERNAME` / `DB_PASSWORD` | Usuario y clave de MySQL (panel del hosting). Use una clave nueva y larga |
| `DB_CHARSET` | `utf8mb4` |
| `DB_PREFIX` | `natillera_` |
| `APP_DEBUG` | `0` en producción (errores genéricos; el detalle va a `logs/app.log`). `1` solo en pruebas |
| `NT_NO_AUTOMIGRATE` | `1` en producción: el sitio nunca cambia la base por sí solo; se migra a mano (paso 4) |
| `NT_AUTOMIGRATE` | Déjelo en `0` (solo para pruebas locales) |
| `APP_URL` | Dirección pública sin barra final, p. ej. `https://natillera.midominio.com`. Solo la usa el verificador (paso 9) |
| `REMOTE_DB_HOST`, `REMOTE_DB_DATABASE`, `REMOTE_DB_USERNAME`, `REMOTE_DB_PASSWORD` | Solo para scripts antiguos de `scripts/`. No hacen falta para operar; déjelas vacías en el servidor |
| `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` | Llaves de notificaciones push. Se generan con `php scripts/generate_vapid_keys.php` (paso 8) |
| `VAPID_SUBJECT` | Contacto del administrador, formato `mailto:correo@tudominio.com` |

Las variables reales del servidor (por ejemplo definidas en el panel) tienen prioridad sobre el `.env`.

## 4. Migraciones de la base

Desde la raíz del proyecto, en este orden:

```
php scripts/migrate.php --status     # lista qué está aplicada y qué PENDIENTE
php scripts/migrate.php --dry-run    # muestra qué se aplicaría, sin tocar nada
php scripts/migrate.php              # aplica las pendientes
```

- Si una migración falla, se detiene y las siguientes no corren: lea el mensaje (detalle en `logs/app.log`), corrija y repita; son idempotentes.
- La migración `unico_ahorros_cuotas` no crea el índice si hay cuotas duplicadas y lista las filas: corríjalas y repita el comando.
- En una base nueva, `migrate.php` imprime una sola vez la cédula y la clave temporal del Presidente inicial: anótela, el sistema pedirá cambiarla.
- Si el usuario MySQL puede crear bases y la base no existe: `php scripts/migrate.php --crear-bd`.

## 5. DocumentRoot, Apache y .htaccess

**Lo ideal:** configure el DocumentRoot (o la "carpeta raíz del dominio") apuntando a `public/`. Así nada fuera de `public/` queda expuesto. Ejemplo de VirtualHost:

```
<VirtualHost *:80>
    ServerName natillera.midominio.com
    DocumentRoot /var/www/infonatillera/public
    <Directory /var/www/infonatillera/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Hosting compartido sin acceso al DocumentRoot:** suba el proyecto completo a la carpeta pública del dominio (por ejemplo `public_html/`). El `.htaccess` de la raíz ya envía todo a `public/` y bloquea `.env`, `.git`, `app/`, `config/`, `core/`, `scripts/`, `docs/`, `logs/`, `storage/`, `*.sql`, `*.md`, etc. Verifique que el archivo `.htaccess` de la raíz se haya copiado (los archivos que empiezan con punto a veces se omiten) y haga la comprobación del paso 9.

Requisitos de Apache:
- `mod_rewrite` activo (`a2enmod rewrite`).
- `AllowOverride All` (o al menos `FileInfo AuthConfig Limit Options`) para el directorio, o los `.htaccess` se ignoran y `/.env` quedaría descargable.
- Si el sitio va en una subcarpeta (`dominio.com/natillera/`) cambie `RewriteBase /` en ambos `.htaccess` (raíz y `public/`) por `/natillera/`.
- Reinicie Apache tras cambiar la configuración: `sudo systemctl reload apache2`.

## 6. Permisos de escritura

El usuario del servidor web (en Debian/Ubuntu `www-data`) debe poder escribir en:

- `public/uploads/` (con `firmas/` y `evidencias/`): firmas y fotos de entregas y préstamos.
- `storage/` (se crea sola; guarda copias del histórico de años).
- `logs/` (registro de errores `app.log`).

```
mkdir -p public/uploads/firmas public/uploads/evidencias storage logs
sudo chown -R www-data:www-data public/uploads storage logs
sudo chmod -R 775 public/uploads storage logs
```

En hosting compartido use el administrador de archivos o el cliente FTP para dar permiso 755 o 775 a esas carpetas. No dé permisos 777.

## 7. Tareas programadas (recordatorios de reunión)

El script `scripts/enviar_recordatorios.php` decide por la hora si envía el aviso de las 8:00 (a todos), 12:00 o 13:00 (solo a quien no ha pagado), y solo si ese día hay reunión programada. No repite avisos. Programe tres ejecuciones diarias (8:00, 12:00 y 13:00, hora de la natillera, `America/Bogota`; si el servidor está en otra zona, ajuste las horas).

**Linux (crontab).** Ejecute `crontab -e` con el usuario del sitio y agregue (cambie las rutas):

```
0 8,12,13 * * * /usr/bin/php /var/www/infonatillera/scripts/enviar_recordatorios.php >> /var/www/infonatillera/logs/recordatorios.log 2>&1
```

En hosting compartido use "Tareas cron" del panel con el mismo comando, una tarea por hora si no acepta listas.

**Windows (Programador de tareas).** Cree tres tareas (o una con tres desencadenadores diarios a las 08:00, 12:00 y 13:00) con:

- Programa: `C:\ruta\a\php\php.exe`
- Argumentos: `scripts\enviar_recordatorios.php`
- Iniciar en: `C:\ruta\a\infonatillera`

O por línea de comandos (una por hora):

```
schtasks /Create /SC DAILY /ST 08:00 /TN "Natillera 0800" /TR "C:\php\php.exe C:\www\infonatillera\scripts\enviar_recordatorios.php"
schtasks /Create /SC DAILY /ST 12:00 /TN "Natillera 1200" /TR "C:\php\php.exe C:\www\infonatillera\scripts\enviar_recordatorios.php"
schtasks /Create /SC DAILY /ST 13:00 /TN "Natillera 1300" /TR "C:\php\php.exe C:\www\infonatillera\scripts\enviar_recordatorios.php"
```

Prueba sin enviar nada: `php scripts/enviar_recordatorios.php --dry-run --ahora="AAAA-MM-DD 08:00"`.

## 8. Rotar claves (obligatorio en este despliegue)

**8.1 Llaves VAPID.** Las que están en `config/vapid_keys.php` estuvieron en git: considérelas comprometidas.

```
php scripts/generate_vapid_keys.php mailto:contacto@sudominio.com
```

Imprime tres líneas (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`): péguelas en el `.env` del servidor. Como la llave pública cambia, las suscripciones anteriores dejan de funcionar: **cada socio debe volver a entrar a Notificaciones y pulsar "Activar en mi Navegador"**. Avíseles. (Los avisos de la campanita siguen funcionando mientras tanto.)

**8.2 Clave de la base de datos remota.** La clave `REMOTE_DB_PASSWORD` (y la de la base que se usó en el pasado) estuvo en el historial de git, así que debe cambiarse:

1. Cambie la contraseña del usuario en el servidor MySQL o en el panel del hosting (`ALTER USER 'usuario'@'host' IDENTIFIED BY 'clave_nueva_larga';` o "cambiar contraseña" en el panel).
2. Inmediatamente actualice `DB_PASSWORD` (y `REMOTE_DB_PASSWORD` si la usa) en el `.env` del servidor.
3. Compruebe con `php scripts/verificar_despliegue.php`.
Entre el paso 1 y el 2 el sitio no podrá conectarse: hágalo en un momento de poco uso.

**8.3 Sacar `config/vapid_keys.php` del control de git (sin borrar el archivo del disco).** Solo como instrucción, ejecútelo usted en su equipo de desarrollo:

```
git rm --cached config/vapid_keys.php
echo config/vapid_keys.php >> .gitignore
git commit -m "Dejar de rastrear config/vapid_keys.php"
```

El archivo sigue en el disco, pero sus llaves viejas continúan en el historial: por eso 8.1 es obligatorio. En el servidor no copie ese archivo.

## 9. Verificación posterior

Ponga la dirección en `APP_URL` del `.env` y ejecute:

```
php scripts/verificar_despliegue.php
```

Muestra una tabla OK / ADVERTENCIA / FALLO (PHP y extensiones, conexión, migraciones pendientes, tablas y columnas clave, claves del `.env` sin mostrar valores, VAPID, permisos, `max_allowed_packet`, DocumentRoot, git). No modifica nada ni imprime contraseñas. Resuelva todos los FALLO.

Lista manual:

- [ ] Abre el sitio con HTTPS y carga estilos e imágenes.
- [ ] Inicio de sesión con el Presidente; si es clave temporal, pide cambiarla.
- [ ] Hay un socio de prueba; se llama a lista y se cierra una reunión de prueba (use una base de prueba o anule después).
- [ ] La firma y la foto de evidencia se guardan y se ven (`public/uploads/firmas`).
- [ ] Notificaciones: activar en el navegador, "Enviarme una prueba" llega.
- [ ] `https://SU-DOMINIO/.env`, `/db.sql`, `/.git/config`, `/scripts/migrate.php`, `/config/database.php` responden 403 o 404 (nunca el contenido).
- [ ] Un error forzado (ruta inexistente) muestra un mensaje genérico, no rutas ni SQL.
- [ ] `php scripts/enviar_recordatorios.php --dry-run` corre sin errores.
- [ ] `logs/app.log` no muestra errores nuevos.

## 10. Cómo volver atrás

1. Ponga el sitio en mantenimiento o detenga el acceso (renombre temporalmente `public/index.php` o apague el VirtualHost).
2. Código: restaure la copia del paso 1.3 (o vuelva a copiar la versión anterior del proyecto) conservando `.env`.
3. Base de datos: restaure el respaldo:
   ```
   mysql -h SERVIDOR -u USUARIO -p NOMBRE_BD < respaldo_AAAA-MM-DD.sql
   ```
   Si ya se aplicaron migraciones, restaurar el respaldo las deshace por completo (la tabla `natillera_migraciones` vuelve a su estado anterior).
4. Archivos: restaure `public/uploads/` desde su respaldo.
5. Si había rotado claves, las nuevas siguen siendo válidas: no vuelva a las comprometidas.
6. Reactive el sitio y repita la lista del paso 9.
