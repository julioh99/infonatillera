# 📋 Módulo 02: Llamado a Lista Quincenal y Autopréstamos

El módulo de Llamado a Lista es el núcleo operativo de las reuniones quincenales de la natillera. Permite registrar masivamente el cobro de cuotas, ahorros extraordinarios y generar autopréstamos automáticos cuando un socio entra en mora.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/LlamadoListaController.php`](../app/Controllers/LlamadoListaController.php)
- **Modelos:**
  - [`app/Models/Reunion.php`](../app/Models/Reunion.php)
  - [`app/Models/Prestamo.php`](../app/Models/Prestamo.php)
- **Vista:** [`app/Views/admin/llamado_lista.php`](../app/Views/admin/llamado_lista.php)

---

## ⚙️ Reglas de Negocio Operativas

### 1. Seleccionador Inteligente de Quincena Activa
Al ingresar a la vista de Llamado a Lista (`/admin/llamado-lista`), el método `Reunion::getReunionActual()` consulta la primera reunión que **aún no tiene registros de asistencia** y cuyo estado no sea `CERRADA`.
- *Ejemplo:* Si las quincenas 1, 2 y 3 ya fueron guardadas, el sistema seleccionará por defecto la **Quincena 4**.

### 2. Registro de Cuota Base y Ahorro Extra
- En cada quincena el valor de la cuota base está prefijado ($55.000 en regulares, $60.000 en rifas, $65.000 en rondas).
- Los socios pueden ingresar un monto adicional en la columna **Ahorro Extra**.

### 3. Autopréstamo Automático al 10%
- Si un socio **no pagó su cuota quincenal**, el sistema permite activar el switch **"Generar Autopréstamo"**.
- Se inserta automáticamente un préstamo por el valor de la cuota base con una **tasa de interés mensual del 10%**.

### 4. Regla de Anulación dentro de las 24 Horas
- Si el socio se pone al día dentro de las **24 horas posteriores** a la reunión, la directiva puede hacer clic en el botón **"Anular Autopréstamo (24h)"**.
- Esto elimina el interés cobrado y devuelve la cuota como pagada sin recargo.

---
[⬅️ Anterior: Autenticación](./01_autenticacion_y_roles.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Préstamos](./03_prestamos_e_intereses.md)
