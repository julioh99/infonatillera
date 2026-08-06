# 👥 Módulo 07: Gestión de Socios y Directorio

El módulo de Gestión de Socios controla el padrón oficial de la natillera, su información personal, cumpleaños, roles administrativos y límites individuales de crédito.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/SocioController.php`](../app/Controllers/SocioController.php)
- **Modelo:** [`app/Models/Usuario.php`](../app/Models/Usuario.php)
- **Vista:** [`app/Views/admin/socios.php`](../app/Views/admin/socios.php)

---

## ⚙️ Funciones Principales

1. **Nuevo Socio:** Botón y modal **"+ Nuevo Socio"** para registrar nuevos integrantes asignándoles cédula, teléfono, cumpleaños, rol y clave inicial.
2. **Edición de Datos Personales:** Permite corregir teléfonos, nombres y fechas de nacimiento.
3. **Control de Cumpleaños:** Widget colapsable que lista los cumpleaños organizados por mes para felicitaciones comunitarias.
4. **Límites de Crédito Personalizados:** Modificación de los topes individuales para préstamos solicitados por socios con buena trayectoria.

---
[⬅️ Anterior: Notificaciones Push](./06_notificaciones_push.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Dashboard Socio](./08_dashboard_socio.md)
