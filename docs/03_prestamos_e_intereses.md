# Módulo 03: Préstamos, intereses y abonos

Administra los préstamos a socios, el cálculo del interés mensual y los abonos a capital e interés.

## Componentes
- Controlador: [`app/Controllers/PrestamoController.php`](../app/Controllers/PrestamoController.php)
- Modelos: [`app/Models/Prestamo.php`](../app/Models/Prestamo.php) y [`app/Models/InteresPrestamo.php`](../app/Models/InteresPrestamo.php) (cálculo puro, sin base de datos)
- Vista: `app/Views/admin/prestamos.php`
- Permisos: registrar y abonar, Presidente, Tesorera y Secretaria General; editar y borrar, Presidente y Secretaria General.

## Reglas

### Préstamos directos
- Se otorgan al socio deudor, con un campo opcional de **referencia o alias** (para qué es o a nombre de quién).
- Tope por socio (`tope_prestamo_personalizado`, $2.000.000 por defecto). Superarlo solo lo autoriza el Presidente o la Secretaria General.
- Todo préstamo entregado queda como **egreso** del cierre de su reunión, tenga o no firma o foto. La constancia de firma y foto (módulo 09) es evidencia adicional.
- No se pueden crear préstamos en una reunión `CERRADA`.

### Interés mensual
Regla vigente (`interes.modo = SALDO_MES_INICIADO` en `config/natillera.php`): **10% mensual sobre el capital pendiente al inicio de cada mes; el mes que empieza se cobra completo**.
- Si el socio abona capital, el mes siguiente paga menos.
- Si paga todo el capital, el interés deja de correr ese día.
- Modos alternativos: `SALDO_MES_COMPLETO` (solo meses cumplidos) y `COMPUESTO` (interés sobre interés no pagado).
- La lista de préstamos muestra interés causado, pagado y pendiente, y el detalle mes a mes; el modal de abono propone el interés pendiente.
- Autopréstamos anulados por la regla de 24 horas: interés 0.
- Prueba: `php tests/test_interes_progresivo.php`.

### Abonos
- Capital e interés se abonan por separado. Se rechazan montos negativos y capital mayor al saldo.
- Al llegar el saldo de capital a cero el préstamo pasa a `PAGADO`.
- Un abono sin reunión asignada se ubica en el cierre por la fecha del abono.
- El Presidente y la Secretaria General pueden editar préstamos y corregir o eliminar abonos del historial de cuotas; el saldo y el estado se recalculan.

### Meta de intereses
Cada socio tiene una meta anual de $400.000 en intereses (`prestamo.meta_intereses_anual`), visible en su panel.

---
[Anterior](./02_llamado_a_lista.md) | [Índice](./README.md) | [Siguiente: Actividades](./04_actividades_tamales.md)
