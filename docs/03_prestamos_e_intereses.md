# 💰 Módulo 03: Gestión de Préstamos e Historial de Cuotas

Este módulo administra los préstamos concedidos a los socios de la natillera, las rentabilidades por intereses generadas y el registro de abonos a capital e interés.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/PrestamoController.php`](../app/Controllers/PrestamoController.php)
- **Modelo:** [`app/Models/Prestamo.php`](../app/Models/Prestamo.php)
- **Vista:** [`app/Views/admin/prestamos.php`](../app/Views/admin/prestamos.php)

---

## ⚙️ Características y Funcionalidades

### 1. Préstamos Directos y Referencias Opcionales
- Los préstamos se otorgan **directamente al socio deudor**.
- Incluyen el campo opcional **Persona Referente / Nombre de Referencia** (*ej: "Para negocio de empanadas" / "Ref: Juan Pérez"*), permitiendo a la directiva identificar con claridad el motivo o referente del préstamo sin necesidad de figuras de fiadores.

### 2. Abonos Independientes a Capital e Interés
- Permite amortizar montos separados para **Capital** e **Interés**.
- Al completar el total del capital prestado, el estado del préstamo pasa automáticamente a `PAGADO ✔`.

### 3. Edición de Préstamos Pagados y Control de Cuotas
- **Modificación por Directiva:** El Presidente y la Secretaria General pueden usar el botón **"✏ Editar"** en cualquier préstamo (incluso en préstamos finalizados `PAGADO`).
- **Historial Editable de Cuotas (`≡ Cuotas`):** Permite abrir el historial de abonos realizados a un préstamo para modificar montos o eliminar registros erróneos.
- El saldo y el estado del préstamo se recalculan en tiempo real tras cualquier ajuste.

---
[⬅️ Anterior: Llamado a Lista](./02_llamado_a_lista.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Actividades (Tamales)](./04_actividades_tamales.md)
