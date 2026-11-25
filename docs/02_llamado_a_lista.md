# Módulo 02: Llamado a lista y autopréstamos

Registro en lote de las cuotas de cada reunión, del ahorro extra y de los autopréstamos de quienes no pagan.

## Componentes
- Controlador: [`app/Controllers/LlamadoListaController.php`](../app/Controllers/LlamadoListaController.php)
- Modelos: [`app/Models/Reunion.php`](../app/Models/Reunion.php) (`guardarLlamadoListaBatch`, `anularAutoprestamo24Horas`) y [`app/Models/Prestamo.php`](../app/Models/Prestamo.php)
- Vista: `app/Views/admin/llamado_lista.php`
- Permisos: Presidente, Tesorera y Secretaria General.

## Reglas

### Reunión activa
`Reunion::getReunionActual()` propone la primera reunión sin registros de asistencia y que no esté `CERRADA`.

### Cuota y su desglose
Cada reunión tiene su cuota (`valor_cuota_base`): $55.000 normal, $60.000 o $65.000 en las reuniones con cuota extra. El desglose por socio es:

| Cuota | Ahorro | Ronda | Rifa |
| :--- | ---: | ---: | ---: |
| $55.000 | $40.000 | $10.000 | $5.000 |
| $60.000 | $40.000 | $10.000 | $10.000 |
| $65.000 | $40.000 | $20.000 | $5.000 |

El ahorro neto es siempre $40.000. La ronda y la rifa de cada pago quedan en `monto_aporte_ronda` y `monto_aporte_rifa` y alimentan los fondos reservados (ver módulo 09). El **ahorro extra** es un monto voluntario adicional.

### Notificación de pago
Al guardar, cada socio que pagó recibe una notificación de pago (ventana que aparece en su panel). Volver a guardar sin cambios no la duplica.

### Autopréstamo
Si el socio no pagó, el interruptor **Generar autopréstamo** crea un préstamo por el valor de la cuota con la tasa mensual de `config/natillera.php` (10%), asociado a su registro de la reunión.

### Regla de 24 horas
- Si el socio paga dentro de las **24 horas siguientes a la creación del autopréstamo**, la directiva usa **Anular autopréstamo (24h)**: el préstamo queda anulado sin interés, la cuota queda pagada y el socio recibe la notificación de pago.
- El plazo se mide con la fecha y hora de creación del autopréstamo (no con la fecha de la reunión). Pasadas 24 horas, o si el autopréstamo ya tiene abonos, la anulación se rechaza con un mensaje.
- Un autopréstamo anulado cuenta interés 0.

### Reuniones cerradas
Una reunión `CERRADA` ya no admite cambios en el llamado a lista. Si hay que corregir algo, la directiva debe seguir el procedimiento del módulo 09 (cierres).

### Integridad
Existe un índice único (`reunion_id`, `socio_id`) en `natillera_ahorros_cuotas`: un socio no puede tener dos registros en la misma reunión.

---
[Anterior](./01_autenticacion_y_roles.md) | [Índice](./README.md) | [Siguiente: Préstamos](./03_prestamos_e_intereses.md)
