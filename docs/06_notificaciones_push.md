# 🔔 Módulo 06: Notificaciones Push Web

Este módulo implementa notificaciones en tiempo real directo al navegador o teléfono móvil del socio mediante la **Web Push API** y Service Workers.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/NotificacionController.php`](../app/Controllers/NotificacionController.php)
- **Modelo:** [`app/Models/Notificacion.php`](../app/Models/Notificacion.php)
- **Cliente JavaScript:** [`public/js/push_client.js`](../public/js/push_client.js)
- **Service Worker:** [`public/sw.js`](../public/sw.js)
- **Vista Admin:** [`app/Views/admin/notificaciones.php`](../app/Views/admin/notificaciones.php)

---

## ⚙️ Flujo Técnico de Funcionamiento

1. **Suscripción del Navegador:** Al iniciar sesión, el cliente `push_client.js` solicita permiso al usuario y registra la `PushSubscription` en el Service Worker `sw.js`.
2. **Registro de Endpoint:** Las claves de suscripción (Endpoint, P256dh, Auth) se envían vía POST a `/admin/notificaciones/suscribir` y se guardan en la base de datos.
3. **Envío Broadcast:** La Secretaria General o el Presidente redactan el mensaje y el sistema transmite la notificación Web Push a los dispositivos activos de todos los socios.

---
[⬅️ Anterior: Programación Reuniones](./05_programacion_reuniones.md) | [📋 Índice](./README.md) | [➡️ Siguiente: Gestión de Socios](./07_gestion_socios.md)
