# task.md · InfoNatillera

Lista de lo que se debe revisar y corregir, y de lo que se agregó el 25/11/2026.
Marca con `[x]` lo que vayas cerrando.

---

## 0. Para el cierre del año que termina este mes (hacer en este orden)

- [ ] **Copia de la base de datos antes de todo**: `mysqldump --single-transaction --routines infonatillera > respaldo_antes_cierre_2026.sql` (local y servidor).
- [ ] Copiar al servidor los archivos nuevos y modificados (lista en la sección 6).
- [ ] Ejecutar `php scripts/create_historico_table.php` (crea `natillera_historico` y la columna `natillera_usuarios.orden`; no borra nada). Igual se crean solas al abrir la app.
- [ ] Revisar que el servidor MySQL acepte paquetes del tamaño del año: `SHOW VARIABLES LIKE 'max_allowed_packet';` (16 MB o más es suficiente; el año comprimido pesa muy poco).
- [ ] Revisar en **Préstamos** la columna "Interés Pagado / Pendiente": ahora se calcula 10% mensual sobre el saldo, cobrando cada mes iniciado. Comparar con la planilla de 2 o 3 socios antes de liquidar.
- [ ] Hacer los cierres de reunión que falten.
- [ ] Entrar como Presidente a **Histórico de Años** → "Guardar este año" con el nombre `Dic 2025 - Nov 2026`.
- [ ] Descargar la copia JSON del año (botón de descarga) y guardarla fuera del servidor.
- [ ] Decidir qué pasa con los préstamos que sigan activos: pagarlos antes del cierre o marcar "Pasar al año nuevo los préstamos que siguen activos".
- [ ] El 1 de diciembre: **Crear año nuevo** (`Dic 2026 - Nov 2027`), elegir si continúan los socios, ordenar la lista y escribir CONFIRMAR.
- [ ] Crear las 26 reuniones del año nuevo en **Reuniones**.

---

## 1. Lo nuevo que se implementó

### 1.1 Interés mensual progresivo
- Clase `app/Models/InteresPrestamo.php` (sin base de datos, se puede probar sola).
- Regla elegida: **10% (la tasa del préstamo) sobre el capital pendiente al inicio de cada mes; el mes que empieza se cobra completo**. Si el socio abona capital, el mes siguiente paga menos. Si paga todo el capital, el interés deja de correr ese día.
- Se configura en `config/natillera.php` → `interes.modo`:
  - `SALDO_MES_INICIADO` (actual), `SALDO_MES_COMPLETO` (solo meses cumplidos), `COMPUESTO` (interés sobre interés no pagado).
- Se muestra en: lista de préstamos (pendiente y meses cobrados), modal de abono (el campo "Abono a Intereses" se llena con lo pendiente), JSON `/admin/prestamos/abonos-json` (detalle mes a mes en `interes.meses`) y dashboard del socio ("+ interés pendiente").
- Autopréstamos anulados por la regla de 24 horas: interés 0.
- Prueba: `php scratch/test_interes_progresivo.php` (9 casos, todos pasan).
- [ ] Pendiente de decidir: ¿el interés debe dejar de correr el 30 de noviembre aunque el préstamo siga activo?
- [ ] Pendiente: el abono no valida que el interés abonado no supere lo pendiente (ver 2.5).

### 1.2 Histórico de años (como ramas de Git)
- Tabla `natillera_historico` (modelo `app/Models/Historico.php`, controlador `app/Controllers/HistoricoController.php`, vista `app/Views/admin/historico.php`, menú "Histórico de Años", solo Presidente).
- Cada año (diciembre a noviembre) es una fila con una foto completa y comprimida de: reuniones, cuotas, préstamos, abonos, actividades, participantes, abonos de actividades, cronograma de rondas y rifas, entregas, inyecciones, cierres, transferencias entre cajas, pagos notificados y notificaciones. También guarda qué socios estaban activos y su orden (nunca las contraseñas).
- **Guardar** = copia lo que hay en las tablas al año cargado (como un commit).
- **Cargar otro año** = guarda el año cargado, vacía las tablas y carga el otro (como un checkout).
- **Crear año nuevo** = guarda el año cargado, vacía las tablas y empieza en blanco. Pregunta si continúan los mismos socios; se pueden desmarcar, arrastrar para ordenar, ordenar A-Z o volver al orden actual. Opcional: pasar préstamos activos como saldo inicial.
- Seguridad de datos:
  - Antes de cargar o crear se hace un **RESPALDO_AUTOMATICO** que no se modifica después, y una copia en archivo en `storage/historico/`.
  - Todo ocurre en una transacción: si algo falla, no cambia nada.
  - Cada foto lleva huella SHA-256; si no coincide, no se restaura.
  - Usuarios, roles y suscripciones push nunca se borran. Quien hace la operación siempre queda activo.
  - Pide escribir CONFIRMAR y lleva token CSRF.
- Probado en MariaDB 10.11: guardar → crear año nuevo → volver al año anterior deja exactamente los mismos conteos, y volver al año nuevo también.
- [ ] Probar el flujo completo en una copia de la base real antes de usarlo en producción.
- [ ] Las fotos y firmas de `public/uploads/` no se copian (solo sus rutas); no borrar esa carpeta.

### 1.3 Orden de socios
- Columna `natillera_usuarios.orden`. Todas las listas de socios (llamado a lista, préstamos, actividades, entregas) se ordenan por ese campo.
- Se cambia desde Histórico de Años → "Guardar solo el orden", o al crear el año nuevo.
- [ ] Agregar el mismo control de orden en Gestión de Socios.

---

## 2. Dinero: préstamos, llamado a lista, cierres (prioridad alta)

> Avance 25/11/2026 (código escrito, sin probar contra la BD ni en navegador): 2.1, 2.3, 2.4, 2.5, 2.11 (solo CierreController), 2.13 (revisado, sin cambio), 3.1 y 3.2. Requieren prueba manual antes de producción. Requiere `natillera_entregas_beneficios.prestamo_id` (`scripts/alter_entregas_prestamo_id.php`). Pendiente: rotar la clave de la BD remota.

- [x] **2.1** Préstamo directo sin firma ni foto no se registra como egreso del cierre (`PrestamoController::guardar` solo crea la entrega con firma o foto; `CierreReunion` suma egresos desde `natillera_entregas_beneficios`). Registrar siempre el egreso.
- [ ] **2.2** El autopréstamo se cuenta como $55.000 de egreso y solo $40.000 de ingreso en el cierre (`Reunion::guardarLlamadoListaBatch` + `CierreReunion::calcularResumenReunion`). Definir cómo se contabiliza: ¿sale efectivo o es solo contable? **La causa probable es que el cierre no suma los $15.000 de ronda y rifa como ingreso: ver 2.14.**
- [x] **2.3** Regla de 24 horas sin control de tiempo (`Reunion::anularAutoprestamo24Horas`): no compara fechas, no revisa abonos, no borra la entrega del autopréstamo y devuelve `true` aunque no encuentre el registro.
- [x] **2.4** Reuniones `CERRADA` se pueden modificar: el llamado a lista borra e inserta las cuotas, los abonos y préstamos no revisan el estado, y el cierre se puede repetir (`ON DUPLICATE KEY UPDATE`) dejando viejo el `saldo_acumulado_caja` de cierres posteriores.
- [x] **2.5** Abonos sin validar (`Prestamo::registrarAbono`, `actualizarAbono`): se acepta capital mayor al saldo y montos negativos.
- [ ] **2.6** Liquidación anual (`SocioController::liquidacionAnual`) cuenta como ahorro la cuota cubierta por autopréstamo sin restar la deuda. Ahora que hay histórico, ya no mezcla años, pero sigue sin restar deudas ni sumar utilidades de actividades.
- [ ] **2.7** Valores quemados: 40.000 de ahorro neto, 10.000/5.000 ronda y rifa, 400.000 de meta, 500.000 por defecto en `EntregaController::guardar`. Pasarlos a `config/natillera.php`.
- [ ] **2.8** Montos leídos con `str_replace('.', '', ...)`: `150000.5` se convierte en 1.500.005. Usar `unformatMoneyString` y validar en el servidor.
- [ ] **2.9** Borrados físicos sin auditoría (eliminar préstamo, abonos, autopréstamos). Agregar borrado lógico y una tabla de auditoría.
- [ ] **2.10** `natillera_ahorros_cuotas` sin índice único (`reunion_id`, `socio_id`).
- [ ] **2.11** Cuando el usuario de la sesión no se encuentra, varios controladores registran la acción a nombre del primer socio (`PrestamoController::abono`, `CierreController::cerrar`, `InyeccionController`, `TransferenciaCajaController`).
- [ ] **2.12** Ronda y rifa: el cierre calcula el total pero lo reporta en 0 y no lo suma. **Se resuelve con 2.14** (fondos reservados).
- [ ] **2.14** **Fondos de Ronda y Rifa: saldo reservado por reunión** (ver detalle abajo).

### 2.14 Detalle: fondos de Ronda y Rifa

**Reglas que describió el usuario**
- Cuota normal $55.000 = $40.000 ahorro + $10.000 ronda + $5.000 rifa. Hay 50 socios.
- Ronda: premio de $300.000, se entrega **en orden de turno**; según lo recaudado salen 1, 2, 3 o 4 personas por reunión.
- Rifa: premio de $150.000, **sorteo entre los 50 socios**.
- Lo que sobra (no alcanza para otro premio) se arrastra a la siguiente reunión. Ese saldo **no se puede gastar**: es plata comprometida que se entrega después.
- Para que los 50 salgan en las 26 reuniones se sube la cuota: $60.000 (+$5.000 a rifa) y $65.000 (+$10.000 a ronda). Esto se planifica en un Google Sheets.

**Ejemplo con cuota normal** (el cálculo actual de `FondoBeneficio` lo reproduce)

| Reunión | Ronda: entra | Disponible | Premios $300.000 | Queda reservado |
|---|---|---|---|---|
| 1 | 500.000 | 500.000 | 1 | 200.000 |
| 2 | 500.000 | 700.000 | 2 | 100.000 |
| 3 | 500.000 | 600.000 | 2 | 0 |

Rifa con $5.000 x 50 = 250.000 por reunión y premio de $150.000: 250.000 → 1 premio (queda 100.000); 350.000 → 2 (queda 50.000); 300.000 → 2 (queda 0).

**Datos reales del Sheets "Cronograma natillera 2026"** (3 hojas: `entrega` = cuota por reunión, `300` = ronda, `80` = rifa). Cada fondo cierra en $0 a la reunión 26 con 50 de 50 socios, y el aporte anual por socio suma $1.490.000 (40.000 x 26 + ronda 300.000 + rifa 150.000).
- Cuota por reunión: $55.000 normal; **$65.000** en las reuniones 4, 8, 16 y 21 (ronda $20.000); **$60.000** en las reuniones 2, 7, 13 y 24 (rifa $10.000). Nunca coinciden dos extras en la misma reunión.
- Personas que reciben, por reunión (1 a 26):
  - **Ronda** ($300.000): 1, 2, 2, 3, 2, 1, 2, 3, 2, 2, 1, 2, 2, 1, 2, 3, 2, 2, 1, 2, 3, 2, 2, 1, 2, 2 (suma 50).
  - **Rifa** ($150.000): 1, 4, 1, 2, 2, 1, 3, 2, 2, 2, 1, 2, 3, 1, 3, 1, 2, 2, 1, 2, 2, 1, 2, 3, 1, 3 (suma 50).
- La ronda sí entrega siempre el máximo que alcanza. **La rifa no**: en las reuniones 7, 14 y 25 entrega menos del máximo (3, 1 y 1 en lugar de 4, 2 y 2) y guarda el resto. Por eso el código actual (que siempre entrega el máximo) no reproduce la hoja de la rifa, y hay que guardar los premios planificados por reunión.
- **Las reuniones con cuota extra son un ejemplo, no una regla fija.** La hoja `80` pone el $60.000 en la reunión 13 y la hoja `entrega` en la 18 (una de las dos se armó solo para mostrar el caso con más equivalencia). Lo ideal es que las reuniones con extra de ronda y de rifa **queden bien separadas entre sí** y no tan cerca. Por eso el sistema no debe fijarlas en el código: se eligen por año al planificar las reuniones.
- La hoja `entrega` tiene reuniones los lunes cada 14 días: 2025-12-15 a 2026-11-30.

**Lo que hay hoy y lo que falla** (`app/Models/FondoBeneficio.php`, `natillera_fondo_beneficios_cronograma`)
- Ya existe un cronograma con este cálculo, pero es solo **plan**: se genera una sola vez (`COUNT > 0` y no vuelve a generarse), así que si cambias la cuota de una reunión queda desactualizado.
- El aporte sale de reglas fijas por cuota (`>= 65000` → ronda 20.000; `60000 a 64999` → rifa 10.000). Una cuota distinta (ej. $70.000) da un total que no cuadra. Los 50 socios, el 40.000 y los premios están escritos en el código.
- Siempre asume el máximo de premios (`floor`). En la realidad el tesorero decide cuántos entregar en cada reunión.
- **No compara contra lo real:** el recaudo real está en `natillera_ahorros_cuotas` (`monto_aporte_ronda`, `monto_aporte_rifa`) y lo entregado en `natillera_entregas_beneficios`, pero el saldo reservado no se calcula con eso.
- **El cierre no los cuenta:** `CierreReunion::calcularResumenReunion` calcula `rondas_rifas` pero devuelve `0.00` (línea ~317) y no lo suma a los ingresos. Esa es la causa del descuadre de **2.2**: el autopréstamo cuenta $55.000 de egreso y solo $40.000 de ingreso porque faltan los $15.000 de ronda y rifa.
- Verificar también si los premios entregados (RONDA/RIFA) se restan como egreso en el cierre; hoy el cierre solo muestra "premios entregados" y no queda claro que lo haga.

**Qué hacer**
- [ ] Guardar por reunión, en vez de deducirlo de la cuota: `aporte_ronda`, `aporte_rifa` y `premios_planificados` (cuántos de ronda y de rifa se entregan). Que se editen desde **Reuniones**, igual que la cuota del Sheets.
- [ ] Regenerar el cronograma cuando cambie la cuota o los aportes de una reunión (no solo la primera vez), sin tocar las reuniones cerradas.
- [ ] Mover a `config/natillera.php` el número de socios, el ahorro de $40.000 y los premios de $300.000 y $150.000.
- [ ] Calcular el **saldo reservado real** por reunión y por fondo: `saldo anterior + aportes reales cobrados - premios realmente entregados`. Mostrar plan y real lado a lado.
- [ ] En el cierre: sumar `rondas_rifas` como ingreso, restar los premios entregados como egreso, y mostrar **"Caja total"**, **"Reservado ronda"**, **"Reservado rifa"** y **"Caja disponible para prestar"** (`total - reservados`).
- [ ] Con eso, cerrar 2.2: el autopréstamo debe aportar el ingreso completo ($55.000 con ronda y rifa) o dejar de contarse como egreso de caja.
- [ ] Ronda: mostrar el siguiente turno y quién ya recibió. Rifa: excluir del sorteo a quienes ya ganaron, para que salgan los 50 en las 26 reuniones.
- [ ] Prueba con los números del ejemplo (500 → 1 y queda 200; 700 → 2 y queda 100; 600 → 2 y queda 0) y con una reunión de $60.000 y otra de $65.000.
- [ ] Cargar los datos del Sheets (cuota, aporte de ronda y de rifa, personas de ronda y de rifa por reunión) como valores iniciales de las 26 reuniones del año.
- [ ] Al planificar el año, **sugerir una distribución separada** de las reuniones con extra (4 de ronda y 4 de rifa, repartidas a distancia pareja y sin que se junten) y **simular** el resultado antes de guardar: cada fondo debe llegar a $0 en la última reunión con todos los socios servidos, y el tesorero puede ajustar a mano.
- [ ] **Cupos variables:** hoy son 50 y casi seguro seguirán así, pero en años futuros pueden reducirse o ampliarse. El número de cupos debe ser un valor por año (en `config/natillera.php` o en el año del histórico), nunca un `50` escrito en el código, y el recaudo de cada fondo debe calcularse con ese valor. Al cambiar los cupos, el sistema debe recalcular cuántas reuniones y cuántas cuotas extra hacen falta para que todos salgan antes de la última reunión.
- [x] **2.13** Abonos sin reunión se asignan al cierre por rango de fechas buscando `numero_quincena - 1`; revisar que funcione en la quincena 1 de cada año.

## 3. Seguridad (prioridad alta)

- [x] **3.1** (código listo; falta rotar la clave) Cambiar la clave de la base de datos remota: está escrita en `scratch/test_remote_db.php`, `scratch/check_remote_users.php` y en 9 archivos de `scripts/` (los 5 `alter_*`, `create_new_tables.php`, `create_sql_views.php`, `create_transferencias_table.php` y `fix_reunion_states.php`); revisar también `migrate_sqlite_to_mysql.php`. Moverla a `.env` y borrarla de esos archivos.
- [x] **3.2** (reglas .htaccess listas; falta apuntar DocumentRoot a public/) Apuntar el servidor web a `public/` (o agregar un `.htaccess` que bloquee el resto). Hoy se podrían descargar `.env`, `natillera2.sqlite`, `db.sql` y ejecutar `scratch/*.php`. Lo mismo para `storage/historico/`.
- [ ] **3.3** CSRF: ya existen `csrfToken()` y `verificarCsrf()` en `core/Controller.php` y los usa Histórico. Falta agregarlos a todos los demás formularios POST.
- [ ] **3.4** La Secretaria General puede asignar el rol Presidente (`SocioController::actualizarSocio`).
- [ ] **3.5** `/socio/liquidacion-anual` muestra ahorros y cédulas de todos a cualquier socio.
- [ ] **3.6** La Tesorera puede fijar cualquier tasa de interés al crear un préstamo.
- [ ] **3.7** Login sin `session_regenerate_id` ni límite de intentos; contraseña mínima de 6 caracteres; clave por defecto `123456` en código y README.
- [ ] **3.8** `EntregaController::guardar` usa `tipo_beneficio` sin validar en el nombre del archivo y no valida que la firma sea una imagen real.
- [ ] **3.9** `EntregaController` cambia `$_SESSION['usuario']['id']` por otro usuario si no lo encuentra.
- [ ] **3.10** Mensajes técnicos visibles (`die()` con el error de PDO, excepciones en pantalla).
- [ ] **3.11** `expedienteJson` permite el rol `Tesorero`, pero el rol se llama `Tesorera`.
- [ ] **3.12** `requireRole` deja pasar al Presidente aunque esté en modo Socio.
- [ ] **3.13** `config/vapid_keys.php` con llaves en el código; no subirlo a ningún repositorio.

## 4. Funciones que dice el README y no existen

- [ ] **4.1** Web Push real: hoy solo se guardan en una tabla y el navegador las consulta. No hay firma VAPID ni envío.
- [ ] **4.2** Recordatorios automáticos de las 8:00, 12:00 y 13:00: no hay cron ni tarea programada.
- [ ] **4.3** Generar las 26 reuniones del año automáticamente al crear el año nuevo.

## 5. Mantenimiento

- [ ] **5.1** Poner el proyecto en Git con un `.gitignore` (`.env`, `*.sqlite`, `public/uploads/`, `storage/`, `config/vapid_keys.php`, `logs/`).
- [ ] **5.2** Migraciones numeradas en lugar de `scripts/alter_*.php` sueltos y de `Database::ensureSchemaUpdates` en cada petición.
- [ ] **5.3** Convertir `scratch/` en pruebas PHPUnit contra una base de prueba (no la real).
- [ ] **5.4** Actualizar README, `info.md` y `resumen.md` (todavía hablan de SQLite y de reglas viejas). `seed.php` usa nombres de tabla sin el prefijo `natillera_` y ya no funciona.
- [ ] **5.5** Partir vistas grandes (`cierre_reunion.php`, `prestamos.php`, `header.php`) y mover el JavaScript a `public/js/`.
- [ ] **5.6** Unificar código repetido: división ahorro/ronda/rifa (3 lugares), guardado de firma y foto (2 controladores), consulta de abonos por fechas (3 veces en `CierreReunion`).
- [ ] **5.7** Agregar una columna de ciclo a las tablas si en algún momento se quieren reportes que comparen varios años sin cargarlos.

## 6. Archivos de este cambio

Nuevos:
- `config/natillera.php`
- `app/Models/InteresPrestamo.php`
- `app/Models/Historico.php`
- `app/Controllers/HistoricoController.php`
- `app/Views/admin/historico.php`
- `scripts/create_historico_table.php`
- `scratch/test_interes_progresivo.php`
- `task.md`

Modificados:
- `config/database.php` (columna `orden` y tabla `natillera_historico` en `ensureSchemaUpdates`)
- `core/Controller.php` (`csrfToken`, `verificarCsrf`)
- `public/index.php` (rutas `/admin/historico/*` y `/admin/socios/ordenar`)
- `app/Models/Prestamo.php` (`adjuntarIntereses`, `calcularInteresPrestamo`)
- `app/Models/Usuario.php` (orden de socios, `guardarOrden`, interés pendiente en el resumen)
- `app/Models/Reunion.php`, `app/Models/Actividad.php`, `app/Models/EntregaBeneficio.php` (orden de socios)
- `app/Controllers/PrestamoController.php` (detalle de interés en `abonos-json`)
- `app/Views/admin/prestamos.php`, `app/Views/socio/dashboard.php`, `app/Views/layouts/header.php`

---

## 7. Rediseño 2027 "Pino y caléndula" (25/11/2026)

Hecho: capa de diseño `public/css/theme.css` (colores, tipografía, tarjetas, botones, formularios, tablas, modales, movimiento) y CSS por grupo de pantallas en `public/css/pages/` (`layout`, `dashboards`, `gestion-dinero`, `gestion-general`). Se rediseñaron el menú, el login, el cambio de clave inicial, los dos tableros y las 10 pantallas de gestión. Solo presentación: no se tocó lógica ni datos. Las skills de diseño están en `.claude/skills/`. Respaldo previo en `J:\www\infonatillera_backups\2026-11-25_antes_rediseno\` (instrucciones en `LEEME_RESTAURAR.md`).

- [x] Revisado en el navegador en móvil (390px): login, menú, tablero del socio, tablero del presidente, socios, préstamos, llamado a lista y cierre de reunión. Escritorio (1360px): tablero del presidente. Las 12 pantallas cargan sin errores de PHP.
- [ ] Revisar a mano, con datos reales: entregas (firma en el modal), actividades, inyecciones, transferencias, notificaciones, reuniones, y los modales de crear/abonar/editar préstamo.
- [ ] Revisar la pantalla de cambio de clave inicial (no se pudo ver sin cambiar la clave de la cuenta de prueba) y el login en escritorio.
- [ ] El HTML que genera JavaScript (lista de notificaciones, desglose de pagos, ventanas de SweetAlert) sigue con clases antiguas; se ve bien por el tema pero no está rediseñado. `confirmButtonColor: '#198754'` en `header.php`.
- [ ] La campana no muestra punto de "sin leer": el CSS está (`.nt-iconbtn[data-has-unread="true"]`) pero falta que el JS ponga el atributo.
- [ ] Textos fijos para parametrizar con el año y los cupos: "50 Socios Activos" (login), "(50 Socios)" (pie), "Normativa Oficial 2026", "ciclo 2026", "Todas (12)" y las cifras del reglamento.
- [ ] La URL `natillera.skylinedev.top` está escrita en los mensajes de WhatsApp de `header.php`.
- [ ] Varios archivos quedaron con saltos de línea LF; Git avisa de la conversión a CRLF y el diff puede traer ruido.
- [ ] Cuenta de prueba 104050: se marcó `password_changed = 0` para que al próximo ingreso obligue a cambiar la clave. La clave temporal que se usó para probar debe cambiarse en ese momento.

---

## 8. Estado al 25/11/2026 (después del trabajo con agentes)

**Hecho y commiteado** (cada punto tiene su commit; las pruebas están en `tests/`, ejecutables con `php tests/run.php` contra una copia local):
- [x] 1.1 Interés mensual progresivo · 1.2 Histórico de años · 1.3 Orden de socios (también en Gestión de Socios).
- [x] 2.1, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9 (auditoría con instantánea restaurable), 2.10 (índice único), 2.11, 2.12, 2.13, 2.14 y 2.2 (caja real con ronda y rifa; liquidez $92.446.000).
- [x] 3.1, 3.2, 3.3 (CSRF central), 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12.
- [x] 4.1 Web Push real · 4.2 recordatorios 8:00/12:00/13:00 · 4.3 `Reunion::generarCiclo`.
- [x] 5.1 `.gitignore` · 5.2 migraciones numeradas · 5.3 pruebas (`tests/run.php`, sin PHPUnit) · 5.4 documentación y `seed.php`.
- [x] Rediseño 2027 de 2 colores, compacto y con transiciones. Tres propuestas de logo en `/logos.html`.
- [x] Migraciones aplicadas en la base remota `skylined_pruebas` (con respaldo previo en `J:\www\infonatillera_backups\2026-11-25_antes_migraciones_remotas\`).

**Pendiente (requiere decisión o acción tuya)**
- [ ] **Rotar la clave de la BD remota** (estuvo en git) y **las llaves VAPID** (`config/vapid_keys.php` sigue rastreado): `php scripts/generate_vapid_keys.php mailto:...`, pegar en `.env` y que los socios reactiven las notificaciones.
- [ ] **Cierres con movimientos posteriores:** el tablero cuadra con los cierres salvo -$2.755.000 por movimientos registrados después de cerrar (R13, R14, R15, R18). `php scripts/reconciliar_cierres.php` los lista; `--aplicar` los reescribe (solo con respaldo y tu OK).
- [ ] **Datos:** la reunión R23 tiene `valor_cuota_base` = 5.500.000 (error de dato); corregirla.
- [ ] **Listas de socios activos:** llamado a lista, préstamos, actividades y entregas aún muestran a todos los socios; al crear un año con socios que no continúan deben usar solo los activos (`getAllSocios(true)` o `getSociosActivos()`).
- [ ] **Valores aún escritos a mano:** `Usuario.php` (400000 de meta), `socio/dashboard.php` y `auth/login.php` (meta $400.000, "50 Socios", "2026"), `public/js/firma_canvas.js` (300000/150000/500000) y `reuniones.php` (cuotas 60000/65000 en el JS).
- [ ] **Firma y foto de préstamos:** `PrestamoController` guarda con decodificación débil; usar el helper seguro de `EntregaController` (mover a una clase común). Código repetido de abonos por fechas en `CierreReunion` (3 consultas).
- [ ] **Llamado a lista:** deduplicar por `socio_id` o usar `ON DUPLICATE KEY UPDATE` (el índice único dejaría de aceptar un socio repetido).
- [ ] **Despliegue:** `php scripts/migrate.php --status`, `--dry-run`, luego sin opciones; apuntar el DocumentRoot a `public/`; crear el cron o las tareas programadas (`docs/06`); `NT_NO_AUTOMIGRATE=1` está activo en el `.env` local.
- [ ] 5.5 partir vistas grandes y mover JS a `public/js/` · 5.7 columna de ciclo en las tablas (no se hicieron: chocan con el rediseño).
- [ ] **Cierre del año (sección 0):** respaldo, copiar al servidor, guardar el año `Dic 2025 - Nov 2026`, descargar la copia JSON y crear el año nuevo el 1 de diciembre. Probar antes el histórico en una copia de la base real.
- [ ] **Logo:** elegir ganador entre las propuestas de `/logos.html` (3 del proyecto + 3 de Gemini, ya cargadas); luego convertir el nombre a trazos y cambiar el ícono del aviso push (`ICONO` en `sw.js`).

---

## 9. Cierre de la jornada del 25/11/2026 (listo para subir)

**Hecho y commiteado hoy**
- [x] Socios activos: llamado a lista, préstamos, actividades, entregas y notificaciones usan solo activos y respetan `orden`; Gestión de Socios sigue mostrando a todos para poder reactivar. Llamado a lista sin duplicados (`ON DUPLICATE KEY UPDATE`).
- [x] Valores fijos a `config/natillera.php` (`web.ciclo_nombre`, `web.app_url`, `web.color_marca`, meta de intereses, premios por tipo en entregas). El año del ciclo del login/pie se cambia en `web.ciclo_nombre`.
- [x] Firma y foto seguras unificadas en `core/Evidencia.php` (Préstamos ahora rechaza imágenes inválidas con mensaje).
- [x] Campana con indicador de "sin leer".
- [x] Guía `docs/DESPLIEGUE.md` y verificador `php scripts/verificar_despliegue.php`.
- [x] Dato R23: `valor_cuota_base` corregido de 5.500.000 a 55.000 (base remota).
- [x] Logos: `/logos.html` con las 6 propuestas y comparación a 32 px.
- [x] Pruebas: 10 de 11 archivos pasan (`seguridad` no puede arrancar su servidor local en este equipo; el CSRF y el login se comprobaron a mano en el navegador).

**Cambio de año (script `scripts/nuevo_anio_socios.php`)**
- Ensayado en una copia local de la base: 50 socios activos en el orden pedido, 45 que continúan (10 con ortografía corregida), 5 nuevos (cédulas y claves temporales nuevas), 5 que quedan inactivos (no se borran), 26 reuniones del 14/12/2026 al 29/11/2027 con las cuotas extra separadas (ronda en 3, 9, 16 y 22; rifa en 6, 12, 19 y 25), y el año anterior guardado con respaldo automático.
- Cruce de nombres que conviene revisar: Marglevis Abuelles = antes "Argumedo"; Gisela Cassas = antes "Jicela Casas"; Yufari Herrera = antes "Yulfarys Herrera"; Yuli Gonzalez = antes "Yulieth Gonzalez"; Rosiris Henriquez = antes "Rosiris Enrique"; Dina Luz Gonzalez = antes "Dina Gonzalez"; "Lina" se conservó como "Lina Arroyo".
- Salen (quedan inactivos): Cecilia Gonzalez, Jhonatan Alvarez, Cristina Fuentes, Antonio Gonzalez, Isabel Fuentes. Cuatro de ellos tienen 9 préstamos activos.
- Los 5 socios nuevos reciben cédula con el esquema 1040+id y una clave temporal (se guarda en un CSV fuera del proyecto); deben cambiarla al primer ingreso. Las cédulas de todo el sistema son números de prueba 1040xx: reemplazarlas por las reales cuando se tengan.
- [x] **Cambio de año ejecutado en la base real (25/11/2026 11:51)**, con las decisiones del usuario: ahora mismo, sin pasar los préstamos activos (45, $23.636.600 de capital pendiente: quedaron solo en el año guardado), primera reunión el 14/12/2026 y nombres dudosos confirmados como las mismas personas. Resultado: año "Dic 2025 - Nov 2026" guardado y verificado (con respaldo automático), año "Dic 2026 - Nov 2027" cargado con 50 socios activos, 5 inactivos, 26 reuniones (14/12/2026 a 29/11/2027; extras de ronda en 3, 9, 16 y 22 y de rifa en 6, 12, 19 y 25), sin cuotas, préstamos ni cierres. Respaldo previo y claves temporales de los 5 socios nuevos en `J:\www\infonatillera_backups\2026-11-25_antes_cambio_anio_real\` (entregar las claves y luego borrar el CSV).
- [ ] **Préstamos que quedaron en el año anterior:** los 45 préstamos activos no están en el año nuevo; para cobrarlos hay que cargar "Dic 2025 - Nov 2026" desde Histórico de Años o registrarlos como saldo inicial.
- [ ] **Liquidación anual del año que cerró:** hacerla antes de borrar nada; el año está guardado y se puede recargar para consultarla.
- [ ] Cédulas de los 5 socios nuevos (104052 a 104056) son números de prueba; cambiar por las reales en Gestión de Socios.

**Sigue pendiente**
- [ ] Elegir el logo con la mesa directiva.
- [ ] Rotar la clave de la BD remota y las llaves VAPID (ver `docs/DESPLIEGUE.md`).
- [ ] `php scripts/reconciliar_cierres.php --aplicar` (-$2.755.000 de movimientos posteriores al cierre en R13, R14, R15 y R18): solo con respaldo y OK del usuario.
- [ ] 5.5 (partir vistas grandes y mover JS) y 5.7 (columna de ciclo): no se hicieron.
- [ ] Revisar a mano en el navegador: firma en entregas, actividades, inyecciones, transferencias, notificaciones y los modales de préstamos.
