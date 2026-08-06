# 📊 Módulo 08: Dashboard Individual del Socio

El Dashboard del Socio proporciona transparencia financiera total, permitiendo a cada socio consultar en cualquier momento su saldo acumulado, rendimiento proyectado y estado de préstamos.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/SocioController.php`](../app/Controllers/SocioController.php)
- **Modelos:**
  - [`app/Models/Usuario.php`](../app/Models/Usuario.php)
  - [`app/Models/Reunion.php`](../app/Models/Reunion.php)
  - [`app/Models/Prestamo.php`](../app/Models/Prestamo.php)
- **Vista:** [`app/Views/socio/dashboard.php`](../app/Views/socio/dashboard.php)

---

## ⚙️ Métricas Visualizadas

1. **Total Ahorrado en Cuotas:** Suma de todas las cuotas fijas pagadas hasta la fecha.
2. **Total Ahorro Extra:** Acumulado de ahorros voluntarios aportados en cada quincena.
3. **Ganancia por Intereses Proyectada:** Cálculo transparente del porcentaje de utilidades generadas por el cobro de intereses a préstamos.
4. **Resumen de Préstamos Activos:** Estado de deudas pendientes, intereses acumulados y saldo a pagar.
5. **Historial de Asistencia:** Tabla interactiva con el detalle de las cuotas quincenales pagadas.

---
[⬅️ Anterior: Gestión de Socios](./07_gestion_socios.md) | [📋 Índice](./README.md)
