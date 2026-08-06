Actúa como un Desarrollador Full-Stack Senior experto en PHP 8.x nativo (orientado a objetos, arquitectura MVC estricta), SQLite3 mediante PDO, JavaScript Vanilla (ES6+) y diseño UI/UX Mobile-First con Bootstrap 5.

Tu objetivo es desarrollar la aplicación web completa para la gestión de una Natillera comunitaria de 50 socios en Colombia.

### STACK Y HERRAMIENTAS:
- Backend: PHP 8.x con arquitectura MVC limpia (Router, Controllers, Models, Views).
- Base de Datos: SQLite3 gestionado mediante PDO.
- Frontend: HTML5, CSS3, JavaScript Vanilla.
- UI Frameworks / Librerías: Bootstrap 5, SweetAlert2 para alertas interactivas, Service Worker nativo + librería WebPush PHP (VAPID) para notificaciones push.

### ARQUITECTURA MVC Y ESTRUCTURA DE ARCHIVOS DEBERÁ SER:
/config
  ├── database.php (PDO SQLite con foreign keys habilitadas)
  └── vapid_keys.php (Configuración Notificaciones Push)
/core
  ├── Router.php
  ├── Controller.php
  └── Model.php
/app
  ├── Controllers/
  │    ├── AuthController.php
  │    ├── LlamadoListaController.php
  │    ├── PrestamoController.php
  │    ├── ActividadController.php
  │    ├── NotificacionController.php
  │    └── SocioController.php
  ├── Models/
  │    ├── Usuario.php
  │    ├── Reunion.php
  │    ├── Prestamo.php
  │    ├── Actividad.php
  │    └── PushSubscription.php
  └── Views/
       ├── layouts/
       │    ├── header.php
       │    └── footer.php
       ├── auth/login.php
       ├── admin/
       │    ├── llamado_lista.php
       │    ├── prestamos.php
       │    ├── actividades.php
       │    └── notificaciones.php
       └── socio/dashboard.php
/public
  ├── css/app.css
  ├── js/
  │    ├── sw.js (Service Worker para Notificaciones Push)
  │    ├── push_client.js
  │    └── llamado_lista.js
  └── index.php (Front Controller)

### LÓGICA DE NEGOCIO Y REGLAS ESPECÍFICAS:

1. ROLES Y SEGUIRIDAD DE ACCESOS:
   - Roles: 'Presidente', 'Tesorera', 'Secretaria Actividades', 'Secretaria General', 'Socio'.
   - El Presidente tiene control y edición total sobre todos los módulos y la opción de cambiar entre vista administrativa y vista como socio personal.
   - Tesorera: Administra el módulo de cobro quincenal y préstamos.
   - Secretaria de Actividades: Administra de manera aislada el módulo de Actividades (ej. Tamales).
   - Secretaria General: Configuración de fechas/horas de reuniones, notificaciones Web Push, modificación de topes de crédito de socios (mayores a $2M) y ajuste de tasas de interés personalizadas.

2. MÓDULO DE LLAMADO A LISTA QUINCENAL (MOBILE-FIRST):
   - Muestra a los 50 socios con el valor asignado a la reunión ($55.000 cuota regular; $60.000 o $65.000 en quincenas con Rifa de $150k o Ronda de $300k).
   - Opciones por socio: Checkbox 'Pagó Cuota', Input 'Ahorro Voluntario', Checkbox 'Autopréstamo' (se activa al desmarcar 'Pagó Cuota').
   - Regla de 24 Horas: Permite al administrador o tesorera anular o borrar el autopréstamo dentro de un margen de 24 horas sin generarle el 10% de interés si el socio trae la cuota en ese lapso.
   - Guardado en lote (Batch Processing) con confirmación vía SweetAlert2.

3. PRÉSTAMOS, EXCEPCIONES Y META DE INTERESES:
   - Tasa estándar del 10% mensual (interés simple). Permite excepciones ingresadas por la Secretaria General.
   - Límite por defecto: $2.000.000 COP, ampliable por la Secretaria General por cada socio.
   - Soporte a préstamos para terceros registrados a nombre de un Socio Fiador.
   - Indicador visual del cumplimiento del requisito de acumular mínimo $400.000 COP en intereses durante el año por socio.

4. ACTIVIDADES ESPECIALES (TAMALES):
   - Control de ingresos y gastos totales por actividad.
   - Cálculo automático de ganancias netas y división equitativa EXCLUSIVAMENTE entre los socios que fueron marcados como participantes en dicha actividad.

5. SISTEMA DE WEB PUSH NOTIFICATIONS:
   - Integración con Service Worker nativo en JavaScript (`/public/js/sw.js`).
   - Envío de notificaciones automáticas en días de reunión (programadas a las 8:00 AM, 12:00 PM y 1:00 PM) indicando la hora y valor de la cuota.
   - Módulo para que la Secretaria General redacte y envíe notificaciones personalizadas e instantáneas a un socio específico o a todos los socios.

6. LIQUIDACIÓN DE FIN DE AÑO:
   - Reparto de las utilidades totales generadas (intereses de préstamos) de forma PROPORCIONAL al saldo ahorrado (cuotas obligatorias + ahorros extras) por cada socio.

Comienza generando la estructura de carpetas, el enrutador central `core/Router.php`, la conexión PDO en `config/database.php` e implementa la primera vista del **Llamado a Lista Mobile-First** con JavaScript Vanilla.