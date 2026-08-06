# 🔐 Módulo 01: Autenticación, Permisos y Modo Dual Presidente

El módulo de Autenticación se encarga del control de acceso de usuarios, inicio de sesión seguro, gestión de sesiones PHP y alternancia entre funciones ejecutivas y de socio para el Presidente.

---

## 🛠️ Clases y Componentes Relacionados

- **Controlador:** [`app/Controllers/AuthController.php`](../app/Controllers/AuthController.php)
- **Modelo:** [`app/Models/Usuario.php`](../app/Models/Usuario.php)
- **Base:** [`core/Controller.php`](../core/Controller.php)
- **Vistas:**
  - Login: [`app/Views/auth/login.php`](../app/Views/auth/login.php)
  - Layout Header/Sidebar: [`app/Views/layouts/header.php`](../app/Views/layouts/header.php)

---

## 🔒 Roles del Sistema

El sistema cuenta con 5 roles predefinidos en la tabla `roles`:

1. **Presidente:** Acceso total a todos los módulos. Cuenta con botón de *Modo Dual*.
2. **Tesorera:** Acceso a *Llamado a Lista* y *Préstamos* (Registro y Abonos).
3. **Secretaria General:** Acceso a *Préstamos* (Edición), *Programación Reuniones*, *Notificaciones Push* y *Gestión Socios*.
4. **Secretaria Actividades:** Acceso exclusivo a la *Gestión de Actividades (Tamales)*.
5. **Socio:** Acceso exclusivo a su *Dashboard Individual*.

---

## 🔄 Modo Dual Presidente

El Presidente de la natillera cumple un doble papel: es el administrador ejecutivo del fondo y a su vez es un socio más que ahorra y solicita préstamos.

- En la barra lateral (Sidebar) el Presidente dispone del botón **"Modo: PRESIDENTE / SOCIO"**.
- Al cambiar a **Modo SOCIO**, los módulos administrativos de la directiva se ocultan de su barra lateral, permitiéndole experimentar el sistema como cualquier socio ordinario.
- La preferencia se almacena en `$_SESSION['active_mode']`.

---

## 🔑 Credenciales Iniciales

Si la base de datos se crea por primera vez, el sistema inserta automáticamente:
- **Cédula:** `1010000001`
- **Contraseña:** `123456`
- **Rol:** Presidente

---
[⬅️ Volver al Índice de Documentación](./README.md) | [➡️ Módulo Siguiente: Llamado a Lista](./02_llamado_a_lista.md)
