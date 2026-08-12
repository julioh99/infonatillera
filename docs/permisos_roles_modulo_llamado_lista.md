# Documentación: Habilitación del Módulo "Llamado a Lista" para la Secretaria General

## 📌 Diagnóstico del Problema

Cuando se habilitaba el enlace de **Llamado a Lista** en el menú de navegación (`header.php`) para el rol `Secretaria General`, el sistema continuaba denegando el acceso o redirigiendo al usuario a su panel de socio (`/socio/dashboard`). 

Esto sucedía porque el control de seguridad del sistema opera en **dos niveles obligatorios**:

1. **Nivel 1 (Vista / Menú)**: [header.php](file:///j:/www/infonatillera/app/Views/layouts/header.php#L56) determina qué enlaces se renderizan visualmente en la barra lateral según la sesión del usuario.
2. **Nivel 2 (Controlador / Backend)**: [LlamadoListaController.php](file:///j:/www/infonatillera/app/Controllers/LlamadoListaController.php#L11) valida mediante la función `$this->requireRole(...)` si la petición HTTP enviada por el navegador tiene los permisos necesarios antes de procesar cualquier acción.

---

## 🛠️ Ajustes Realizados

### 1. Actualización del Controlador ([LlamadoListaController.php](file:///j:/www/infonatillera/app/Controllers/LlamadoListaController.php))
Se modificaron los métodos principales para autorizar explícitamente a la **Secretaria General**:
- `index()` (Consulta y renderizado de la tabla de llamado a lista).
- `guardarBatch()` (Registro masivo de asistencias, pagos de cuota y cuotas atrasadas).
- `anularAutoprestamo24H()` (Anulación preventiva de autopréstamos automáticos).

```php
// Antes (Restringido solo a Presidente y Tesorera):
$this->requireRole(['Presidente', 'Tesorera']);

// Ahora (Habilitado para Secretaria General):
$this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);
```

### 2. Sincronización del Menú Lateral ([header.php](file:///j:/www/infonatillera/app/Views/layouts/header.php))
Se autorizó el rol `Secretaria General` tanto en el menú de escritorio como en el menú lateral móvil Offcanvas:

```php
<?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
    <li>
        <a class="nav-link <?= strpos($currentUri, '/admin/llamado-lista') === 0 ? 'active' : '' ?>" href="/admin/llamado-lista">
            <i class="fa-solid fa-clipboard-check text-success"></i>
            <span>Llamado a Lista</span>
        </a>
    </li>
<?php endif; ?>
```

---

## 📋 Resumen de Permisos de la Directiva

| Módulo | Presidente | Tesorera | Secretaria General | Secretaria Actividades |
| :--- | :---: | :---: | :---: | :---: |
| **Llamado a Lista** | ✅ | ✅ | ✅ *(Habilitado)* | ❌ |
| **Préstamos** | ✅ | ✅ | ✅ | ❌ |
| **Entregas Rondas/Rifas** | ✅ | ✅ | ✅ | ❌ |
| **Actividades** | ✅ | ❌ | ❌ | ✅ |
| **Cierre de Reunión** | ✅ | ✅ | ✅ | ❌ |
| **Transferencias entre Cajas** | ✅ | ✅ | ✅ | ❌ |
| **Programación Reuniones** | ✅ | ❌ | ✅ | ❌ |
| **Notificaciones Push** | ✅ | ❌ | ✅ | ❌ |
| **Gestión de Socios** | ✅ | ❌ | ✅ | ❌ |
| **Inyecciones Capital** | ✅ | ✅ | ✅ | ❌ |

---

> [!TIP]
> Cada vez que se desee dar acceso a un nuevo rol a cualquier módulo, es indispensable actualizar tanto la condición visual en `app/Views/layouts/header.php` como el método `$this->requireRole([...])` en el controlador correspondiente en `app/Controllers/`.
