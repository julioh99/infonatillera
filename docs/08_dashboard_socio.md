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

## ⚙️ Métricas y Secciones Visualizadas

1. **Total Ahorrado en Cuotas y Extras:** Suma de todas las cuotas fijas pagadas y ahorros opcionales aportados.
2. **Progreso de Meta de Intereses ($400.000 COP):** Indicador gráfico del nivel de cumplimiento del compromiso anual de intereses.
3. **Mis Préstamos y Seguimiento de Cuotas:**
   - Visualización de créditos vigentes y pagados con su tasa de interés, saldo de capital y referente/alias.
   - Botón **"Ver Cuotas"** para desplegar el desglose detallado de cada cuota abonada: fecha, abono a capital, abono a interés y la persona de la directiva que recibió/registró dicho pago.
4. **Mis Actividades Comunitarias y Deudas:**
   - Panel dedicado a eventos comunitarios (venta de tamales, actividades extraordinarias).
   - Muestra la cuota asignada por actividad, el monto pagado, el saldo pendiente individual y el estado (`PAGADO` o `PENDIENTE`).
5. **Historial de Asistencia Quincenal:** Tabla con el detalle de cuotas aportadas reunión por reunión.

---
[⬅️ Anterior: Gestión de Socios](./07_gestion_socios.md) | [📋 Índice](./README.md)
