# InfoNatillera: reglas del sistema en una página

Resumen de las reglas que el sistema aplica hoy. El detalle de cada módulo está en [`docs/`](./docs/README.md) y los valores configurables en [`config/natillera.php`](./config/natillera.php).

## Cuota, ahorro, ronda y rifa
- 50 socios, 26 reuniones por ciclo (diciembre a noviembre).
- Cuota normal **$55.000** = $40.000 ahorro + $10.000 ronda + $5.000 rifa. El ahorro neto del socio es siempre $40.000.
- Cuota **$60.000**: +$5.000 a la rifa. Cuota **$65.000**: +$10.000 a la ronda. Cada año se eligen las reuniones con cuota extra, separadas entre sí, de modo que los 50 socios reciban su premio antes de la última reunión.
- **Ronda** ($300.000): se entrega por turno; salen 1 a 4 personas por reunión según lo recaudado. **Rifa** ($150.000): sorteo entre quienes aún no han ganado.
- Lo que sobra en cada fondo y no alcanza para otro premio se **reserva** para la reunión siguiente. No se presta ni se gasta.

## Préstamos
- Interés mensual del 10% sobre el capital pendiente; cada mes iniciado se cobra completo. Si el socio abona capital, el mes siguiente paga menos; si paga todo, el interés deja de correr.
- No se aceptan abonos negativos ni de capital mayor al saldo.
- Los montos sobre el tope del socio ($2.000.000 por defecto) solo los autoriza el Presidente o la Secretaria General.
- Meta de intereses por socio: $400.000 al año (indicador en el panel del socio).
- Todo préstamo entregado queda como egreso del cierre, con o sin constancia de firma y foto.

## Autopréstamos y regla de 24 horas
- Si un socio no paga la cuota, la directiva genera un autopréstamo por el valor de la cuota.
- Si el socio paga dentro de las 24 horas siguientes a la creación del autopréstamo, se anula sin interés y se notifica el pago. Después de 24 horas, o con abonos hechos, no se puede anular.

## Reuniones, cierres y años
- Una reunión `CERRADA` no se modifica. El cierre suma cuotas, ahorro extra, abonos, intereses, ronda y rifa como ingresos, resta préstamos entregados, premios, inyecciones devueltas y préstamos a la caja de actividades, y arrastra el saldo acumulado.
- El cierre muestra Caja total, Reservado ronda, Reservado rifa y Caja disponible para prestar.
- **Histórico de años:** cada año se guarda como una foto completa y comprimida con huella SHA-256; se puede cargar otro año o crear el año nuevo (solo Presidente, pide escribir CONFIRMAR, hace respaldo automático antes y todo ocurre en una transacción).

## Actividades
- Las actividades (tamales) llevan ingresos, gastos y cuota por socio; la utilidad se reparte entre los participantes. Los préstamos entre la caja mayor y la caja de actividades no cobran interés.

## Acceso y seguridad
- Cinco roles: Presidente, Tesorera, Secretaria General, Secretaria Actividades y Socio. El Presidente puede alternar entre su vista administrativa y su vista de socio.
- No hay clave por defecto. Al crear un socio se genera una clave temporal aleatoria que se muestra una sola vez; el socio debe cambiarla al entrar.
- Formularios con token CSRF, límite de intentos de login, archivos internos bloqueados (`.htaccess`, DocumentRoot en `public/`), credenciales en `.env`, errores técnicos solo en `logs/app.log`.

## Base de datos y operación
- MySQL/MariaDB con prefijo `natillera_`. Los cambios de esquema son migraciones numeradas en `scripts/migrations/`, que se aplican con `php scripts/migrate.php`.
- Pruebas: `php tests/run.php` contra una base local de pruebas. Despliegue, respaldos y restauración: ver [README.md](./README.md).
