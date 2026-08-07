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
2. **Hoja del Socio / Expediente Financiero Completo:** Al hacer clic sobre el nombre del socio o en el botón **"📁 Ver Hoja del Socio"**, se abre un expediente dinámico que consolida:
   - **Metricas KPI:** Total ahorrado (cuotas $40.000 + extras), intereses abonados a la meta ($400k), deudas activas (préstamos y actividades) y beneficios recibidos.
   - **Cuotas y Ahorros de Reunión:** Desglose quincenal de cuotas pagadas ($40.000) y ahorros voluntarios.
   - **Actividades Comunitarias:** Registro de cuotas asignadas en eventos (tamales, rifas), montos pagados, saldo pendiente e historial de abonos.
   - **Préstamos e Intereses:** Capital prestado, amortizaciones a capital, saldo restante, e intereses abonados.
   - **Entregas y Comprobantes:** Registro de rondas, rifas y desembolsos con fotos de comprobantes y firmas digitales en alta resolución.
3. **Edición de Datos Personales:** Permite corregir teléfonos, nombres y fechas de nacimiento.
4. **Control de Cumpleaños:** Widget colapsable que lista los cumpleaños organizados por mes para felicitaciones comunitarias.
5. **Límites de Crédito Personalizados:** Modificación de los topes individuales para préstamos solicitados por socios con buena trayectoria.

---
[⬅️ Anterior: Notificaciones Push](./06_notificaciones_push.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Dashboard Socio](./08_dashboard_socio.md)
