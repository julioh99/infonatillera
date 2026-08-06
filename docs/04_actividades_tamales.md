# 🫔 Módulo 04: Gestión de Actividades Comunitarias (Tamales)

Este módulo gestiona la venta colectiva de alimentos o actividades extraordinarias (como venta de tamales) para incrementar la bolsa común y las utilidades al final del año.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/ActividadController.php`](../app/Controllers/ActividadController.php)
- **Modelo:** [`app/Models/Actividad.php`](../app/Models/Actividad.php)
- **Vista:** [`app/Views/admin/actividades.php`](../app/Views/admin/actividades.php)

---

## ⚙️ Reglas Operativas

1. **Creación de Actividad:** La **Secretaria de Actividades** o el Presidente pueden definir una actividad con su nombre, costo por unidad y fecha límite de entrega.
2. **Asignación por Socio:** Se registra la cantidad de unidades asignadas o vendidas por cada socio.
3. **Control de Recaudo:** Permite marcar si el socio entregó el dinero del recaudo o si tiene saldo pendiente.
4. **Balance Final:** Los fondos generados por actividades alimentan los excedentes repartibles al finalizar la natillera en diciembre.

---
[⬅️ Anterior: Préstamos](./03_prestamos_e_intereses.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Programación Reuniones](./05_programacion_reuniones.md)
