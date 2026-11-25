# Módulo 08: Panel individual del socio

Transparencia financiera: cada socio consulta su estado de cuenta en cualquier momento.

## Componentes
- Controlador: [`app/Controllers/SocioController.php`](../app/Controllers/SocioController.php)
- Modelos: `Usuario`, `Reunion`, `Prestamo`, `PagoNotificacion`
- Vista: `app/Views/socio/dashboard.php`

## Qué muestra
1. **Ahorro:** cuotas de $40.000 pagadas más ahorros extra.
2. **Meta de intereses** ($400.000 al año): barra de avance.
3. **Mis préstamos:** capital, saldo, tasa, referencia, interés pendiente ("+ interés pendiente") y botón **Ver cuotas** con fecha, capital, interés y quién recibió cada pago.
4. **Mis actividades comunitarias:** cuota asignada, pagado, saldo, estado y desglose de abonos.
5. **Historial quincenal:** cuotas reunión por reunión.
6. **Notificaciones de pago:** ventana que aparece cuando la directiva registra un pago; el socio confirma que la vio.
7. **Notificaciones generales** (campana) con los mensajes de la directiva.

El Presidente ve este mismo panel al activar el modo Socio. Cada socio solo ve sus propios datos; la vista de liquidación de todos los socios es para la directiva.

---
[Anterior](./07_gestion_socios.md) | [Índice](./README.md) | [Siguiente: Entregas, cierres y años](./09_entregas_rondas_rifas.md)
