# Módulo 04: Actividades comunitarias (tamales)

Venta colectiva de productos u otros eventos para aumentar las utilidades de fin de año.

## Componentes
- Controlador: [`app/Controllers/ActividadController.php`](../app/Controllers/ActividadController.php)
- Modelo: [`app/Models/Actividad.php`](../app/Models/Actividad.php)
- Vista: `app/Views/admin/actividades.php`
- Permisos: Presidente y Secretaria Actividades.
- Tablas: `natillera_actividades`, `natillera_actividad_participantes`, `natillera_abonos_actividades`.

## Reglas
1. **Actividad y cuotas diferenciadas:** nombre, fecha, ingresos, gastos y la cuota que debe cada socio participante (por ejemplo 3 tamales = $30.000). El botón **Aplicar cuota base** pone un valor común y se ajustan las excepciones.
2. **Abonos parciales:** cada pago del socio es un registro con fecha, monto y responsable. Los montos usan separador de miles con punto.
3. **Sin sobrepagos:** ningún abono puede superar el saldo pendiente (validado en pantalla y en el servidor). Con saldo cero el formulario se deshabilita.
4. **AJAX:** registrar o borrar un abono refresca la lista sin cerrar el modal.
5. **Identificación:** la tabla de participantes muestra el número de socio y tiene buscador por número o nombre.
6. **Visibilidad:** la directiva ve el recaudo completo; el socio ve sus cuotas, abonos y saldo en su panel (**Mis actividades comunitarias**).
7. **Utilidad:** ingresos menos gastos se reparte en partes iguales solo entre los socios participantes de esa edición.
8. **Caja de actividades:** los préstamos entre la caja mayor y la caja de actividades se registran como transferencias entre cajas, sin interés (ver módulo 09). Los préstamos a la caja de actividades salen como egreso del cierre y las devoluciones entran como ingreso.

---
[Anterior](./03_prestamos_e_intereses.md) | [Índice](./README.md) | [Siguiente: Reuniones](./05_programacion_reuniones.md)
