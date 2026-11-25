# Módulo 05: Programación de reuniones, ronda y rifa

Calendario del ciclo (26 reuniones, una cada dos semanas, de diciembre a noviembre), cuota de cada reunión y plan de premios.

## Componentes
- Controlador: [`app/Controllers/ReunionController.php`](../app/Controllers/ReunionController.php)
- Modelos: [`app/Models/Reunion.php`](../app/Models/Reunion.php) y [`app/Models/FondoBeneficio.php`](../app/Models/FondoBeneficio.php)
- Vista: `app/Views/admin/reuniones.php`
- Permisos: Presidente y Secretaria General.
- Valores base: [`config/natillera.php`](../config/natillera.php) (`cupos`, `reuniones_por_ciclo`, `cuota`, `ronda.premio`, `rifa.premio`).

## Cuotas
- **Normal: $55.000** = $40.000 ahorro + $10.000 ronda + $5.000 rifa.
- **$60.000:** +$5.000 a la rifa (rifa $10.000).
- **$65.000:** +$10.000 a la ronda (ronda $20.000).
- Con 50 socios y esta distribución, cada fondo termina en $0 en la reunión 26 con los 50 socios servidos; el aporte anual por socio suma $1.490.000.
- **Qué reuniones llevan cuota extra no está fijo en el código.** Se eligen cada año al planificar, procurando que las de ronda y las de rifa queden separadas entre sí y que nunca coincidan en la misma reunión.

## Premios por reunión
- **Ronda ($300.000):** se entrega en orden de turno; salen 1, 2, 3 o 4 personas según lo recaudado más lo reservado.
- **Rifa ($150.000):** sorteo entre los socios que no han ganado; el tesorero decide cuántos entregar (puede ser menos del máximo y guardar el resto).
- Cada reunión guarda sus aportes de ronda y rifa y los premios planificados; el cronograma se recalcula al cambiar esos valores, sin tocar reuniones cerradas. Ver módulo 09 para el saldo reservado.

## Estados de una reunión
`PROGRAMADA` (futura), `EN_PROCESO` (la que se está atendiendo), `LLAMADO_CERRADO` (llamado a lista guardado) y `CERRADA` (cierre financiero hecho; ya no se modifica nada).

## Cómo se programa
- **Nueva reunión** agrega fechas; se pueden editar fecha, hora, cuota y aportes.
- Al empezar un año nuevo (módulo 09) las 26 reuniones se crean en **Reuniones**.

---
[Anterior](./04_actividades_tamales.md) | [Índice](./README.md) | [Siguiente: Notificaciones](./06_notificaciones_push.md)
