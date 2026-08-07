# 🫔 Módulo 04: Gestión de Actividades Comunitarias (Tamales)

Este módulo gestiona la venta colectiva de alimentos o actividades extraordinarias (como venta de tamales) para incrementar la bolsa común y las utilidades al final del año.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/ActividadController.php`](../app/Controllers/ActividadController.php)
- **Modelo:** [`app/Models/Actividad.php`](../app/Models/Actividad.php)
- **Vista:** [`app/Views/admin/actividades.php`](../app/Views/admin/actividades.php)

---

## ⚙️ Reglas Operativas

1. **Creación de Actividad y Cuotas Diferenciadas:** La **Secretaria de Actividades** o el Presidente pueden definir una actividad especificando su nombre, fecha, ingresos recaudados, gastos y asignando a cada socio seleccionado su **cuota/deuda individual a pagar** (*ej: Socio A 3 tamales = $30.000, Socio B 5 tamales = $50.000*).
2. **Autocompletado de Cuota Base:** Se dispone de un botón *"Aplicar Cuota Base"* para establecer rápidamente un valor común a todos los socios activos y ajustar únicamente las excepciones.
3. **Abonos Parciales por Separado:** Cada pago parcial efectuado por el socio (*ej: primer pago $12.000, segundo pago $24.000, tercer pago $50.000*) se almacena en la tabla `abonos_actividades` manteniendo el registro individual de fechas, montos y responsable.
4. **Visibilidad Dual (Directiva y Socio):**
   - **Directiva:** Desde el modal *"Participantes y Recaudo"* en `/admin/actividades`, la directiva puede registrar abonos parciales individualmente y expandir la lista *"Abonos"* para ver el desglose y eliminar abonos erróneos.
   - **Socio:** En su panel `/socio/dashboard` (*Mis Actividades Comunitarias*), el socio dispone del botón *"Ver mis abonados"* para consultar el desglose exacto y las fechas de cada entrega parcial realizada.
5. **Balance Final:** Los fondos y ganancias netas generadas por actividades se distribuyen entre los socios o alimentan los excedentes repartibles de fin de año.

---
[⬅️ Anterior: Préstamos](./03_prestamos_e_intereses.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Programación Reuniones](./05_programacion_reuniones.md)
