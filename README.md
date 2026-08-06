# InfoNatillera - Software de Control de Natillera Comunitaria 🪙

**InfoNatillera** es un sistema web integral diseñado para la administración, contabilidad y control financiero de una **Natillera Comunitaria de 50 Socios**. Facilita la recolección de cuotas quincenales, préstamos con interés, actividades extraordinarias (tamales), rifas/rondas internas y distribución final de excedentes.

---

## 🚀 Características Principales

- **Gestión Quincenal Inteligente (26 Quincenas):** Detección y selección automática de la siguiente quincena pendiente para agilizar el llamado a lista.
- **Cobro de Cuotas y Autopréstamos:** Registro instantáneo de cuota base ($55.000), ahorros extraordinarios y generación automática de autopréstamos al 10% de interés cuando un socio no paga la cuota.
- **Regla de Anulación de Autopréstamo (24 Horas):** Permite anular el autopréstamo generado si el socio se pone al día dentro de las primeras 24 horas sin cobrar interés.
- **Gestión Simplificada de Préstamos:** Préstamos directos a socios con campo opcional de *Persona Referente / Alias*. Amortizaciones independientes a capital e interés.
- **Flexibilidad Directiva:** El Presidente y la Secretaria General pueden editar préstamos e historial de cuotas pagadas en cualquier momento para correcciones.
- **Rifas e Incentivos:** Soporte para cuotas incrementales en eventos de Rifa ($60.000 / Premio $150.000) y Ronda de Turnos ($65.000 / Premio $300.000).
- **Venta de Actividades (Tamales):** Control de ingresos por eventos comunitarios.
- **Notificaciones Push del Navegador:** Alertas Web Push enviadas a la comunidad sobre fechas de reunión y recordatorios de pago.
- **Modo Dual Presidente:** Permite al Presidente alternar en un clic entre la vista ejecutiva de administración y su vista personal como Socio.

---

## 🛠️ Tecnologías Utilizadas

- **Lenguaje Principal:** PHP 7.2.34 / PHP 8.x (Arquitectura MVC sin dependencias externas pesadas).
- **Base de Datos:** SQLite 3 (`natillera.sqlite`) con auto-creación de tablas, roles y datos de inicio en código.
- **Estilos:** HTML5, CSS3, Bootstrap 5.3, FontAwesome 6, Google Fonts (*Outfit* & *Inter*).
- **Notificaciones:** Web Push API con Service Worker JavaScript.
- **Servidor Web Compatibilidad:** Apache / Laragon (VirtualHost `http://infonatillera.test/`) y servidor incorporado de PHP (`php -S localhost:8001`).

---

## 📁 Estructura del Proyecto

```text
infonatillera/
├── app/
│   ├── Controllers/     # Controladores (AuthController, LlamadoLista, Prestamo, etc.)
│   ├── Models/          # Modelos de BD (Usuario, Reunion, Prestamo, Actividad, etc.)
│   └── Views/           # Vistas PHP / HTML (Admin, Socio, Layouts)
├── config/
│   └── database.php     # Conexión PDO SQLite y Auto-Inicialización / Migraciones
├── core/
│   ├── Controller.php   # Clase Base de Controladores
│   ├── Model.php        # Clase Base de Modelos
│   └── Router.php       # Enrutador Front Controller (Normalización Laragon)
├── docs/                # Documentación detallada por cada módulo
├── public/
│   ├── css/             # Hojas de estilo personalizadas (app.css / Sidebar)
│   ├── js/              # Cliente de Notificaciones Push
│   ├── index.php        # Punto de entrada de la aplicación
│   └── sw.js            # Service Worker para Push Notifications
├── db.sql               # Esquema DDL SQL base
└── natillera.sqlite     # Archivo de base de datos SQLite
```

---

## ⚙️ Instalación y Configuración

### 1. Requisitos Previos
- PHP 7.2 o superior con extensión `pdo_sqlite` habilitada.
- Servidor local como **Laragon**, **XAMPP** o CLI de PHP.

### 2. Puesta en Marcha con Laragon
1. Ubicar la carpeta del proyecto en `C:\laragon\www\infonatillera` (o `J:\www\infonatillera`).
2. Abrir el navegador en `http://infonatillera.test/` o `http://infonatillera.test/login`.

### 3. Puesta en Marcha con Servidor PHP Incorporado
Ejecutar en la terminal dentro de la carpeta del proyecto:
```bash
php -S localhost:8001 -t public public/index.php
```
Navegar a `http://localhost:8001/login`.

---

## 🔑 Credenciales por Defecto (Auto-Inicializadas)

Cuando el sistema arranca por primera vez o se elimina la base de datos `natillera.sqlite`, se crea automáticamente el usuario Presidente inicial guardado en código:

- **Cédula:** `1010000001`
- **Contraseña:** `123456`
- **Rol:** Presidente

---

## 📚 Documentación de Módulos

Consulta las Guías Detalladas de cada Módulo en la carpeta [`docs/`](./docs/README.md):

1. 🔐 [Autenticación y Roles](./docs/01_autenticacion_y_roles.md)
2. 📋 [Llamado a Lista Quincenal](./docs/02_llamado_a_lista.md)
3. 💰 [Préstamos e Intereses](./docs/03_prestamos_e_intereses.md)
4. 🫔 [Actividades Comunitarias (Tamales)](./docs/04_actividades_tamales.md)
5. 📅 [Programación de Reuniones y Eventos](./docs/05_programacion_reuniones.md)
6. 🔔 [Notificaciones Push](./docs/06_notificaciones_push.md)
7. 👥 [Gestión de Socios y Directorio](./docs/07_gestion_socios.md)
8. 📊 [Dashboard del Socio](./docs/08_dashboard_socio.md)

---

## 👥 Tabla de Roles y Permisos

| Módulo / Función | Presidente | Tesorera | Secretaria General | Secretaria Actividades | Socio |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Llamado a Lista** | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Registrar Préstamos** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Editar Préstamos / Cuotas** | ✅ | ❌ | ✅ | ❌ | ❌ |
| **Actividades (Tamales)** | ✅ | ❌ | ❌ | ✅ | ❌ |
| **Programación Reuniones** | ✅ | ❌ | ✅ | ❌ | ❌ |
| **Notificaciones Push** | ✅ | ❌ | ✅ | ❌ | ❌ |
| **Gestión de Socios** | ✅ | ❌ | ✅ | ❌ | ❌ |
| **Mi Dashboard Personal** | ✅ | ✅ | ✅ | ✅ | ✅ |

---
*InfoNatillera &copy; 2026 - Sistema de Gestión de Natillera Comunitaria.*
