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
3. **Control de Recaudo y Saldos por Socio:** La directiva puede usar el botón *"Ver Participantes y Pagos"* para registrar los abonos entregados por cada socio a la actividad.
4. **Balance Final:** Los fondos y ganancias netas generadas por actividades se distribuyen entre los socios o alimentan los excedentes repartibles de fin de año.

---
[⬅️ Anterior: Préstamos](./03_prestamos_e_intereses.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Programación Reuniones](./05_programacion_reuniones.md)
