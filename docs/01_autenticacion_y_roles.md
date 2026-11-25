# Módulo 01: Autenticación, permisos y modo dual del Presidente

Controla quién entra, qué ve y qué puede hacer cada rol.

## Componentes
- Controlador: [`app/Controllers/AuthController.php`](../app/Controllers/AuthController.php)
- Modelo: [`app/Models/Usuario.php`](../app/Models/Usuario.php)
- Base: [`core/Controller.php`](../core/Controller.php) (`requireRole`, CSRF) y [`core/Csrf.php`](../core/Csrf.php)
- Vistas: `app/Views/auth/login.php` y `app/Views/layouts/header.php` (menú)
- Reglas de seguridad configurables: sección `seguridad` de [`config/natillera.php`](../config/natillera.php)

## Roles (tabla `natillera_roles`)
1. **Presidente:** acceso a todo, más el Histórico de años (solo él) y el modo dual.
2. **Tesorera:** llamado a lista, préstamos y abonos, entregas, cierre de reunión, inyecciones y transferencias entre cajas.
3. **Secretaria General:** lo mismo que la Tesorera, más editar préstamos y cuotas, reuniones, notificaciones y gestión de socios.
4. **Secretaria Actividades:** actividades (tamales).
5. **Socio:** su panel personal.

Cada acción se protege en dos niveles: el menú (`header.php`) decide qué enlaces se ven y el controlador valida con `requireRole(...)` antes de ejecutar nada. Si agregas un permiso, cámbialo en los dos.

## Modo dual del Presidente
El Presidente también es socio. El botón **Modo: PRESIDENTE / SOCIO** de la barra lateral oculta los módulos administrativos y le muestra el sistema como a cualquier socio. Se guarda en `$_SESSION['active_mode']`.

## Claves y primer ingreso
- **No existe clave por defecto.** Al crear un socio el sistema genera una **clave temporal aleatoria** y la muestra **una sola vez** a quien lo creó; hay que entregársela al socio.
- Mientras `password_changed = 0`, cualquier página redirige al **cambio de clave obligatorio**. Mínimo 8 caracteres (`seguridad.password_min`).
- En una base nueva, `php scripts/migrate.php` crea el Presidente (cédula `1010000001`) con una clave temporal aleatoria impresa **solo por consola**. Si se pierde, un administrador con acceso a la base puede dejar `password_changed = 0` y asignar otra clave generada con `password_hash`.

## Protecciones
- **CSRF:** formularios y llamadas AJAX que modifican datos envían el token; sin él se responde 419 ("tu sesión expiró").
- **Límite de intentos:** tras `seguridad.login_max_intentos` fallos (5) por cédula o IP, el acceso se bloquea `seguridad.login_bloqueo_min` minutos (15). Los intentos se registran en `natillera_login_intentos`.
- **Sesión:** se regenera el identificador al iniciar sesión.
- **Errores:** mensajes genéricos al usuario; detalle en `logs/app.log`.

---
[Índice](./README.md) | [Siguiente: Llamado a lista](./02_llamado_a_lista.md)
