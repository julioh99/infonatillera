# InfoNatillera

Sistema web para administrar una **natillera comunitaria de 50 socios**: cuotas quincenales, ahorro, préstamos con interés, autopréstamos, ronda y rifa, actividades (tamales), cierres de reunión, histórico de años y notificaciones.

Las reglas de negocio (cupos, cuotas, premios, interés, seguridad) viven en [`config/natillera.php`](./config/natillera.php) y no están escritas dentro de los controladores.

---

## Reglas de negocio vigentes

- **Ciclo:** de diciembre a noviembre, **26 reuniones** (una cada dos semanas). Cada año queda guardado en el **Histórico de años** y se empieza uno nuevo.
- **Cuota normal: $55.000** = $40.000 de ahorro + $10.000 de ronda + $5.000 de rifa. El ahorro neto del socio es siempre $40.000.
- **Cuotas extra** para que los 50 socios reciban su turno en las 26 reuniones: **$60.000** (+$5.000 a la rifa) o **$65.000** (+$10.000 a la ronda). Qué reuniones llevan cuota extra se decide cada año al planificar las reuniones; no está fijo en el código.
- **Ronda:** premio de $300.000 entregado **por turno**. Según lo recaudado salen 1, 2, 3 o 4 personas por reunión. **Rifa:** premio de $150.000 por **sorteo** entre los socios que aún no han ganado.
- **Fondos reservados:** lo que sobra y no alcanza para otro premio se arrastra a la siguiente reunión. Es dinero comprometido: no se presta ni se gasta. El cierre muestra caja total, reservado de ronda, reservado de rifa y caja disponible para prestar.
- **Préstamos:** interés mensual del 10% sobre el saldo de capital pendiente; el mes que empieza se cobra completo (modo configurable en `config/natillera.php`, clave `interes.modo`). Meta de intereses por socio: $400.000 al año.
- **Autopréstamo:** si un socio no paga su cuota, se genera un préstamo por el valor de la cuota. **Regla de 24 horas:** si paga dentro de las 24 horas siguientes a la creación del autopréstamo, la directiva lo anula sin interés. Pasado ese plazo, o si ya tiene abonos, no se puede anular.
- **Reuniones cerradas:** una reunión `CERRADA` no admite cambios en llamado a lista, abonos ni préstamos; el cierre calcula el saldo acumulado de caja y los siguientes cierres dependen de él.
- **Egresos:** todo préstamo entregado queda registrado como egreso del cierre, tenga o no firma o foto de la constancia.

## Módulos

| Módulo | Quién lo usa | Guía |
| :--- | :--- | :--- |
| Autenticación, roles y modo dual | Todos | [01](./docs/01_autenticacion_y_roles.md) |
| Llamado a lista y autopréstamos | Presidente, Tesorera, Sec. General | [02](./docs/02_llamado_a_lista.md) |
| Préstamos e intereses | Presidente, Tesorera, Sec. General | [03](./docs/03_prestamos_e_intereses.md) |
| Actividades (tamales) | Presidente, Sec. Actividades | [04](./docs/04_actividades_tamales.md) |
| Reuniones, ronda y rifa | Presidente, Sec. General | [05](./docs/05_programacion_reuniones.md) |
| Notificaciones push | Presidente, Sec. General | [06](./docs/06_notificaciones_push.md) |
| Gestión de socios | Presidente, Sec. General | [07](./docs/07_gestion_socios.md) |
| Panel del socio | Todos | [08](./docs/08_dashboard_socio.md) |
| Entregas, cierres, fondos de ronda y rifa, histórico de años | Presidente, Tesorera, Sec. General (histórico: solo Presidente) | [09](./docs/09_entregas_rondas_rifas.md) |
| Identidad visual 2027 "Pino y caléndula" | Todos | [10](./docs/10_identidad_visual_logo.md) |

Índice completo en [`docs/README.md`](./docs/README.md).

## Roles y permisos

| Función | Presidente | Tesorera | Sec. General | Sec. Actividades | Socio |
| :--- | :---: | :---: | :---: | :---: | :---: |
| Llamado a lista | Sí | Sí | Sí | No | No |
| Registrar préstamos y abonos | Sí | Sí | Sí | No | No |
| Editar préstamos y cuotas pagadas | Sí | No | Sí | No | No |
| Entregas, cierre de reunión, inyecciones, transferencias entre cajas | Sí | Sí | Sí | No | No |
| Actividades (tamales) | Sí | No | No | Sí | No |
| Reuniones | Sí | No | Sí | No | No |
| Notificaciones | Sí | No | Sí | No | No |
| Gestión de socios | Sí | No | Sí | No | No |
| Histórico de años | Sí | No | No | No | No |
| Mi panel personal | Sí | Sí | Sí | Sí | Sí |

## Tecnología

- PHP 7.2 o superior (sin Composer ni dependencias externas), arquitectura MVC propia (`core/`, `app/Controllers`, `app/Models`, `app/Views`).
- MySQL 5.7+ o MariaDB 10.x con PDO. **No se usa SQLite**: el archivo `natillera2.sqlite` es un resto histórico y no lo lee nada. Todas las tablas llevan el prefijo `natillera_`.
- Bootstrap 5.3, FontAwesome 6, tema propio en `public/css/theme.css`. Web Push con Service Worker (`public/sw.js`).

## Estructura

```text
infonatillera/
├── .env                 # credenciales (NO se sube a Git ni se copia a otros equipos)
├── .env.example         # plantilla de variables de entorno
├── .htaccess            # bloquea el acceso web a todo lo que no sea public/
├── app/                 # Controllers, Models, Views y routes/ (rutas por módulo)
├── config/              # database.php (conexión y migraciones), natillera.php (reglas de negocio)
├── core/                # Router, Controller, Model, Csrf, Money, WebPush
├── docs/                # guías por módulo y de pruebas locales
├── logs/                # app.log (errores técnicos; se crea solo; no se publica)
├── public/              # único directorio que debe servir el servidor web
├── scripts/             # migrate.php, migrations/, generar_db_sql.php y utilidades
├── storage/             # copias del histórico de años (se crea solo; no se publica)
├── tests/               # pruebas (php tests/run.php)
├── db.sql               # esquema de referencia (solo estructura)
└── seed.php             # datos de demostración para una base de pruebas
```

## Puesta en marcha local

1. Crea una base vacía: `CREATE DATABASE infonatillera CHARACTER SET utf8mb4;`
2. Copia `.env.example` a `.env` y completa `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
3. Crea el esquema y los datos iniciales: `php scripts/migrate.php`. En una base nueva crea el usuario Presidente (cédula `1010000001`) con una **clave temporal aleatoria que se imprime una sola vez en la consola**. Guárdala: el sistema obliga a cambiarla en el primer ingreso. No hay clave por defecto.
4. Arranca el servidor: `php -S localhost:8001 -t public public/index.php` y abre `http://localhost:8001/login` (o usa Laragon con el VirtualHost apuntando a `public/`).

## Despliegue en el servidor

Antes de tocar nada haz una copia: `mysqldump --single-transaction --routines infonatillera > respaldo_antes_de_actualizar.sql`.

1. **Copia los archivos** del proyecto al servidor (sin `.env`, `public/uploads/`, `storage/`, `logs/` ni `config/vapid_keys.php` si ya existen allá; esos son del servidor).
2. **`.env`**: créalo o revísalo a partir de `.env.example` (credenciales de la base, `APP_DEBUG=0` en producción; las claves VAPID de las notificaciones están descritas en [docs/06](./docs/06_notificaciones_push.md)). Permisos: legible solo por el usuario del servidor web.
3. **Migraciones:** `php scripts/migrate.php` (revisa antes con `php scripts/migrate.php --dry-run` o `--status`). Es idempotente y nunca repite una migración aplicada. **El despliegue siempre incluye este paso manual**: primero `--status` y `--dry-run` para revisar qué se va a aplicar, después la ejecución real. La aplicación automática por petición está **desactivada por defecto** (así ninguna migración con datos corre sin revisión); solo se activa con la variable explícita `NT_AUTOMIGRATE=1` (o `NT_NO_AUTOMIGRATE=0`) y se recomienda únicamente en entornos locales de prueba. `NT_NO_AUTOMIGRATE=1` la apaga siempre.
4. **DocumentRoot:** apunta el servidor web a la carpeta `public/`. Si el hosting no lo permite, el `.htaccess` de la raíz bloquea `.env`, `.sql`, `.md`, `app/`, `config/`, `scripts/`, `logs/`, `storage/` y similares, pero apuntar a `public/` es la solución segura. Comprueba desde el navegador que `/.env`, `/db.sql` y `/config/database.php` respondan 403 o 404.
5. **Carpetas escribibles** por el servidor web: `public/uploads/`, `storage/`, `logs/`.
6. **Cron de recordatorios** (notificaciones de reunión): ver [docs/06](./docs/06_notificaciones_push.md).
7. **Prueba rápida:** entra, revisa el panel del Presidente y que `logs/app.log` no muestre errores nuevos.

Migraciones: cada cambio de base de datos es un archivo `scripts/migrations/AAAAMMDD_HHMM_nombre.php` que devuelve `['descripcion' => '...', 'up' => function (PDO $db) {...}]` y debe ser idempotente (comprobar `information_schema` antes de `ALTER`/`CREATE`). El ejecutor las aplica en orden de nombre, una sola vez, y las anota en `natillera_migraciones` con fecha y huella SHA-256. Si una falla, se detiene y las siguientes no se ejecutan; el detalle queda en `logs/app.log`. La línea base `00000000_0000_baseline` reemplaza lo que antes se comprobaba en cada petición.

Para regenerar el esquema de referencia `db.sql` (solo estructura, sin datos) después de añadir migraciones:

```bash
DB_DATABASE=nt_higiene php scripts/migrate.php
DB_DATABASE=nt_higiene php scripts/generar_db_sql.php
```

## Pruebas locales

No se prueba nunca contra la base remota. Las pruebas usan una copia local indicada por variables de entorno (ver [docs/PRUEBAS_LOCALES.md](./docs/PRUEBAS_LOCALES.md)); `tests/_bootstrap.php` se niega a correr si el host no es local o la base no parece de pruebas (`nt_*`, `natillera_test` o `*_test`).

```bash
DB_HOST=127.0.0.1 DB_DATABASE=nt_higiene DB_USERNAME=root DB_PASSWORD= php tests/run.php
php tests/run.php autoprestamo      # solo las pruebas cuyo nombre contenga ese texto
```

`tests/run.php` descubre `tests/*_test.php` y `tests/test_*.php`, ejecuta cada archivo como un proceso y devuelve código 0 si todo pasó. Las pruebas con base de datos crean sus propios socios y reuniones y los borran al terminar.

## Respaldos y restauración

- **Respaldo:** `mysqldump --single-transaction --routines infonatillera > respaldo_AAAA-MM-DD.sql`, más una copia de `public/uploads/` (firmas y fotos; en la base solo se guardan sus rutas) y de `storage/historico/`.
- **Antes de operaciones grandes** (cierre de año, cargar otro año del histórico) haz el respaldo a mano; el sistema además guarda un `RESPALDO_AUTOMATICO` del histórico antes de cargar o crear un año.
- **Restaurar en una base vacía:** `CREATE DATABASE infonatillera_restaurada CHARACTER SET utf8mb4;` y `mysql infonatillera_restaurada < respaldo_AAAA-MM-DD.sql`. Después `php scripts/migrate.php` con `DB_DATABASE=infonatillera_restaurada` aplica las migraciones que el respaldo aún no tenga. Verifica con `php scripts/migrate.php --status` y entra a revisar antes de apuntar el `.env` a la base restaurada.
- **Restaurar sobre la base actual** solo si hace falta: primero un respaldo nuevo de lo que hay, luego `mysql infonatillera < respaldo.sql`. Restaura también `public/uploads/` si se perdió.

## Seguridad

- **CSRF:** todos los formularios y peticiones que modifican datos llevan token; sin él la petición se rechaza.
- **Contraseñas:** se guardan con `password_hash`. No existe clave por defecto: al crear un socio se genera una clave temporal aleatoria que se muestra **una sola vez** a quien lo creó, y el socio debe cambiarla al entrar. Mínimo 8 caracteres.
- **Límite de intentos de login:** por cédula e IP, con bloqueo temporal (`seguridad.login_max_intentos` y `seguridad.login_bloqueo_min` en `config/natillera.php`) y regeneración del id de sesión al entrar.
- **Archivos internos:** `.htaccess` bloquea el acceso web a todo lo que no sea `public/`; la solución completa es apuntar el DocumentRoot a `public/`.
- **`.env`:** guarda las credenciales fuera del código; está en `.gitignore`. Rota la clave de la base si alguna vez estuvo en un archivo del proyecto.
- **Errores:** el usuario ve un mensaje genérico; el detalle va a `logs/app.log`. Con `APP_DEBUG=1` en `.env` (solo en desarrollo) también se muestra el detalle.
- **Datos que no se suben a Git:** `.env`, `*.sqlite`, `public/uploads/`, `storage/`, `config/vapid_keys.php`, `logs/`.

---
InfoNatillera 2026-2027. Sistema de gestión de natillera comunitaria.
