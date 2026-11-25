# InfoNatillera: resumen para quien llega nuevo

**Qué es:** una aplicación web en PHP (sin Composer) y MySQL para llevar la natillera de 50 socios: cuotas quincenales, préstamos, autopréstamos, ronda y rifa, actividades, cierres de reunión e histórico de años.

**Dónde está cada cosa**
- Reglas configurables: `config/natillera.php`. Conexión, `.env` y migraciones automáticas: `config/database.php`.
- Rutas: `public/index.php` y `app/routes/` (un archivo por módulo). Lógica: `app/Controllers` y `app/Models`. Pantallas: `app/Views`. Estilos: `public/css/theme.css` (tema "Pino y caléndula" 2027).
- Esquema de la base: migraciones en `scripts/migrations/` y referencia en `db.sql`.
- Guías por módulo: `docs/`. Pendientes y decisiones abiertas: `task.md`.

**Cómo arrancar**
1. `.env` desde `.env.example`; base vacía creada en MySQL.
2. `php scripts/migrate.php` (crea tablas y el usuario Presidente con clave temporal que se imprime una vez).
3. `php -S localhost:8001 -t public public/index.php`.

**Cómo probar:** `DB_DATABASE=nt_higiene DB_USERNAME=root DB_PASSWORD= php tests/run.php` (solo bases locales de pruebas; nunca la remota).

**Reglas que más se preguntan**
- Cuota $55.000 = $40.000 ahorro + $10.000 ronda + $5.000 rifa; $60.000 y $65.000 suben la rifa o la ronda en reuniones elegidas cada año.
- Los fondos de ronda y rifa sobrantes quedan reservados y no se prestan.
- Interés del 10% mensual sobre el saldo; el mes iniciado se cobra completo.
- Autopréstamo anulable sin interés solo dentro de las 24 horas; reuniones cerradas no se modifican.
- Sin clave por defecto: claves temporales aleatorias que se muestran una vez.

**Operación:** despliegue, cron de recordatorios, respaldos y restauración están en `README.md` y `docs/06_notificaciones_push.md`.
