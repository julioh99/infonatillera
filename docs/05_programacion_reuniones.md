# 📅 Módulo 05: Programación de Reuniones, Rifas y Rondas

El módulo de Programación de Reuniones administra el calendario completo de las **26 quincenas** del ciclo anual de la natillera, regulando las fechas de encuentro, montos de cuota base y pasarelas de premiación.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/ReunionController.php`](../app/Controllers/ReunionController.php)
- **Modelo:** [`app/Models/Reunion.php`](../app/Models/Reunion.php)
- **Vista:** [`app/Views/admin/reuniones.php`](../app/Views/admin/reuniones.php)

---

## ⚙️ Esquema de Cuotas y Pasarelas

En las 26 quincenas del año se contemplan 3 tipos de eventos:

1. **Quincena Regular ($55.000 COP):** Cuota base estándar de ahorro.
2. **Pasarela de Rifa Interna ($60.000 COP):** La cuota se incrementa $5.000 COP para entregar un premio acumulado de **$150.000 COP** al socio ganador.
3. **Pasarela de Ronda de Turno ($65.000 COP):** La cuota se incrementa $10.000 COP para entregar un premio mayor de **$300.000 COP** al socio favorecido.

### Programación y Modificación
- La directiva puede hacer clic en **"+ Nueva Reunión"** para adicionar quincenas adicionales.
- Se pueden editar fechas, horas, valor de la cuota base y seleccionar al socio ganador de los sorteos.

---
[⬅️ Anterior: Actividades](./04_actividades_tamales.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Notificaciones Push](./06_notificaciones_push.md)
