# Módulo 09: Entregas, cierres de reunión, fondos de ronda y rifa, histórico de años

## Componentes
- Entregas: `EntregaController`, `EntregaBeneficio` (firma y foto en `public/uploads/firmas/` y `public/uploads/evidencias/`).
- Cierre: `CierreController`, `CierreReunion`. Inyecciones de capital: `InyeccionController`. Transferencias entre cajas: `TransferenciaCajaController`.
- Fondos: `FondoBeneficio`, tabla `natillera_fondo_beneficios_cronograma`.
- Histórico: `HistoricoController`, `Historico`, tabla `natillera_historico` (copias en `storage/historico/`).
- Permisos: entregas, cierres, inyecciones y transferencias, Presidente, Tesorera y Secretaria General; histórico, solo Presidente.

## Entregas con firma y foto
- Sirven para préstamos (opción principal), ronda y rifa. Pad de firma en canvas y foto desde cámara o archivo.
- Desde Préstamos, el botón **Firma/Foto** abre la constancia ligada al préstamo (`prestamo_id`).
- El préstamo cuenta como egreso del cierre aunque no tenga constancia.

## Fondos de ronda y rifa
- Cada reunión guarda su aporte de ronda y de rifa y los premios planificados. Premios: ronda $300.000 por turno, rifa $150.000 por sorteo (`config/natillera.php`).
- **Saldo reservado** = saldo anterior + aportes cobrados - premios entregados. Es dinero comprometido: no se presta.
- Ejemplo con cuota normal, ronda ($500.000 entran por reunión): reunión 1, 1 premio y queda $200.000; reunión 2, disponible $700.000, 2 premios y queda $100.000; reunión 3, disponible $600.000, 2 premios y queda $0.
- El plan (cronograma) se compara con lo realmente cobrado y entregado.

## Cierre de reunión
- **Ingresos:** cuotas de ahorro, ahorro extra, ronda y rifa cobradas, abonos a capital, intereses, inyecciones de capital, devoluciones de la caja de actividades.
- **Egresos:** préstamos entregados, premios de ronda y rifa entregados, inyecciones devueltas, préstamos a la caja de actividades.
- Muestra **Caja total**, **Reservado ronda**, **Reservado rifa** y **Caja disponible para prestar** (total menos reservados) y arrastra el saldo acumulado a la reunión siguiente.
- El autopréstamo cuenta la cuota completa como ingreso (con ronda y rifa) y como egreso.
- Cerrar una reunión la pasa a `CERRADA`: ya no se pueden cambiar su llamado a lista, abonos ni préstamos. El cierre no se repite ni se sobrescribe.
- **Inyecciones de capital:** aporte de un socio con rendimiento pactado; no se puede retirar antes de 6 meses; el retiro se asigna a la reunión elegida y sale como egreso.
- **Transferencias entre cajas:** préstamo a la caja de actividades y devolución a la caja mayor, sin interés.

## Histórico de años (solo Presidente)
- Cada año (diciembre a noviembre) es una foto completa y comprimida: reuniones, cuotas, préstamos, abonos, actividades, entregas, inyecciones, cierres, transferencias, notificaciones, cronograma de fondos y orden de socios (nunca las claves). Lleva huella SHA-256; si no coincide no se restaura.
- **Guardar este año**, **Cargar otro año** (guarda el actual, vacía las tablas y carga el otro) y **Crear año nuevo** (guarda el actual y empieza en blanco; se elige qué socios continúan y su orden; opción de pasar préstamos activos).
- Seguridad: respaldo `RESPALDO_AUTOMATICO` antes de cargar o crear, copia en `storage/historico/`, todo en una transacción, escribir CONFIRMAR y token CSRF. Usuarios, roles y suscripciones push nunca se borran.
- No guarda los archivos de `public/uploads/`, solo sus rutas: no borres esa carpeta.
- Cierre de año sugerido: respaldo de la base, cierres pendientes, "Guardar este año" con nombre `Dic 2025 - Nov 2026`, descargar la copia JSON, decidir los préstamos activos, y el 1 de diciembre "Crear año nuevo" y planificar las 26 reuniones.

---
[Anterior](./08_dashboard_socio.md) | [Índice](./README.md)
