<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>InfoNatillera</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/app.css">
    <!-- Tema 2027 (se carga al final para sobrescribir) -->
    <link rel="stylesheet" href="/css/theme.css?v=<?= @filemtime(__DIR__ . '/../../../public/css/theme.css') ?: 1 ?>">
    <!-- Estructura global y pantallas de acceso -->
    <link rel="stylesheet" href="/css/pages/layout.css?v=<?= @filemtime(__DIR__ . '/../../../public/css/pages/layout.css') ?: 1 ?>">
    <meta name="theme-color" content="#14573d">
    <!-- CSRF central: agrega el token a formularios y a fetch (ver public/js/csrf.js) -->
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <script src="/js/csrf.js?v=<?= @filemtime(__DIR__ . '/../../../public/js/csrf.js') ?: 1 ?>"></script>
    <script src="/js/ui.js?v=<?= @filemtime(__DIR__ . '/../../../public/js/ui.js') ?: 1 ?>" defer></script>
</head>
<body class="bg-light-gradient min-vh-100">

<?php if (isset($_SESSION['usuario'])): 
    $role = $_SESSION['usuario']['rol_nombre'] ?? '';
    $activeMode = $_SESSION['active_mode'] ?? $role;
    $isAdminAccess = ($role === 'Presidente' && $activeMode === 'Presidente') || in_array($role, ['Tesorera', 'Secretaria General', 'Secretaria Actividades']);
    $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($currentUri, '/public/') === 0) {
        $currentUri = substr($currentUri, 7);
    }
?>

    <!-- Sidebar Navegación Desktop -->
    <aside class="app-sidebar shadow-lg">
        <div class="sidebar-brand d-flex align-items-center justify-content-between">
            <a class="nt-brand" href="/socio/dashboard">
                <span class="nt-brand__mark"><i class="fa-solid fa-coins"></i></span>
                <span class="nt-brand__name">InfoNatillera</span>
            </a>
        </div>

        <?php if ($role === 'Presidente'): ?>
            <div class="nt-mode-wrap">
                <form action="/toggle-mode" method="POST" class="m-0">
                    <button type="submit" class="btn nt-mode fw-semibold">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>Modo: <strong class="text-uppercase"><?= htmlspecialchars($activeMode) ?></strong></span>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <ul class="sidebar-nav">
            <?php if ($isAdminAccess): ?>
                <li class="nav-header">Módulos Directiva</li>

                <?php if ($role === 'Presidente' && $activeMode === 'Presidente'): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/dashboard-presidente') === 0 ? 'active' : '' ?>" href="/admin/dashboard-presidente">
                            <i class="fa-solid fa-crown text-warning"></i>
                            <span>Tablero Presidencia</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/llamado-lista') === 0 ? 'active' : '' ?>" href="/admin/llamado-lista">
                            <i class="fa-solid fa-clipboard-check text-success"></i>
                            <span>Llamado a Lista</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/prestamos') === 0 ? 'active' : '' ?>" href="/admin/prestamos">
                            <i class="fa-solid fa-hand-holding-dollar text-warning"></i>
                            <span>Préstamos</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/entregas') === 0 ? 'active' : '' ?>" href="/admin/entregas">
                            <i class="fa-solid fa-gift text-warning"></i>
                            <span>Entregas Rondas/Rifas</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['Presidente', 'Secretaria Actividades'])): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/actividades') === 0 ? 'active' : '' ?>" href="/admin/actividades">
                            <i class="fa-solid fa-utensils text-info"></i>
                            <span>Actividades</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/cierre-reunion') === 0 ? 'active' : '' ?>" href="/admin/cierre-reunion">
                            <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
                            <span>Cierre de Reunión</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/transferencias-cajas') === 0 ? 'active' : '' ?>" href="/admin/transferencias-cajas">
                            <i class="fa-solid fa-arrows-split-up-and-left text-warning"></i>
                            <span>Transferencias entre Cajas</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['Presidente', 'Secretaria General'])): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/reuniones') === 0 ? 'active' : '' ?>" href="/admin/reuniones">
                            <i class="fa-solid fa-calendar-days text-warning"></i>
                            <span>Programación Reuniones</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/notificaciones') === 0 ? 'active' : '' ?>" href="/admin/notificaciones">
                            <i class="fa-solid fa-bell text-danger"></i>
                            <span>Notificaciones Push</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/socios') === 0 ? 'active' : '' ?>" href="/admin/socios">
                            <i class="fa-solid fa-users-gear text-primary"></i>
                            <span>Gestión Socios</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/inyecciones') === 0 ? 'active' : '' ?>" href="/admin/inyecciones">
                            <i class="fa-solid fa-chart-line text-success"></i>
                            <span>Inyecciones Capital</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($role === 'Presidente' && $activeMode === 'Presidente'): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/historico') === 0 ? 'active' : '' ?>" href="/admin/historico">
                            <i class="fa-solid fa-clock-rotate-left text-warning"></i>
                            <span>Histórico de Años</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <li class="nav-header">Mi Cuenta</li>
            <li>
                <a class="nav-link <?= strpos($currentUri, '/socio/dashboard') === 0 ? 'active' : '' ?>" href="/socio/dashboard">
                    <i class="fa-solid fa-chart-line text-primary"></i>
                    <span>Mi Información</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-user-footer">
            <div class="nt-user">
                <div class="nt-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="overflow-hidden">
                    <div class="nt-user__name text-truncate"><?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?></div>
                    <small class="nt-role"><?= htmlspecialchars($_SESSION['usuario']['rol_nombre']) ?></small>
                </div>
            </div>
            <div class="nt-user-actions">
                <button type="button" class="btn nt-action btnAbrirNotificaciones" data-bs-toggle="modal" data-bs-target="#modalMisNotificaciones" title="Centro de Notificaciones">
                    <i class="fa-solid fa-bell"></i><span>Avisos</span>
                </button>
                <button type="button" class="btn nt-action" data-bs-toggle="modal" data-bs-target="#modalEditarMiPerfil">
                    <i class="fa-solid fa-user-pen"></i><span>Perfil</span>
                </button>
                <a href="/logout" class="btn nt-action nt-action--danger">
                    <i class="fa-solid fa-right-from-bracket"></i><span>Salir</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Header Móvil (< 992px) -->
    <header class="navbar navbar-dark bg-glass-dark d-lg-none sticky-top nt-topbar">
        <a class="nt-brand" href="/socio/dashboard">
            <span class="nt-brand__mark"><i class="fa-solid fa-coins"></i></span>
            <span class="nt-brand__name">InfoNatillera</span>
        </a>
        <div class="nt-topbar__actions">
            <button type="button" class="nt-iconbtn btnAbrirNotificaciones" data-bs-toggle="modal" data-bs-target="#modalMisNotificaciones" title="Notificaciones y Comunicados" aria-label="Notificaciones y Comunicados">
                <i class="fa-solid fa-bell"></i>
            </button>
            <button class="nt-iconbtn navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </header>

    <!-- Offcanvas Mobile Sidebar -->
    <div class="offcanvas offcanvas-start nt-offcanvas text-white d-lg-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
            <div class="offcanvas-title nt-brand" id="offcanvasSidebarLabel">
                <span class="nt-brand__mark"><i class="fa-solid fa-coins"></i></span>
                <span class="nt-brand__name">InfoNatillera</span>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            <?php if ($role === 'Presidente'): ?>
                <div class="nt-mode-wrap">
                    <form action="/toggle-mode" method="POST" class="m-0">
                        <button type="submit" class="btn nt-mode fw-semibold">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span>Modo: <strong class="text-uppercase"><?= htmlspecialchars($activeMode) ?></strong></span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <ul class="sidebar-nav">
                <?php if ($isAdminAccess): ?>
                    <li class="nav-header">Módulos Directiva</li>

                    <?php if ($role === 'Presidente' && $activeMode === 'Presidente'): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/dashboard-presidente') === 0 ? 'active' : '' ?>" href="/admin/dashboard-presidente">
                                <i class="fa-solid fa-crown text-warning"></i>
                                <span>Tablero Presidencia</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/llamado-lista') === 0 ? 'active' : '' ?>" href="/admin/llamado-lista">
                                <i class="fa-solid fa-clipboard-check text-success"></i>
                                <span>Llamado a Lista</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/prestamos') === 0 ? 'active' : '' ?>" href="/admin/prestamos">
                                <i class="fa-solid fa-hand-holding-dollar text-warning"></i>
                                <span>Préstamos</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/entregas') === 0 ? 'active' : '' ?>" href="/admin/entregas">
                                <i class="fa-solid fa-gift text-warning"></i>
                                <span>Entregas Rondas/Rifas</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($role, ['Presidente', 'Secretaria Actividades'])): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/actividades') === 0 ? 'active' : '' ?>" href="/admin/actividades">
                                <i class="fa-solid fa-utensils text-info"></i>
                                <span>Actividades</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/cierre-reunion') === 0 ? 'active' : '' ?>" href="/admin/cierre-reunion">
                                <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
                                <span>Cierre de Reunión</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/transferencias-cajas') === 0 ? 'active' : '' ?>" href="/admin/transferencias-cajas">
                                <i class="fa-solid fa-arrows-split-up-and-left text-warning"></i>
                                <span>Transferencias entre Cajas</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($role, ['Presidente', 'Secretaria General'])): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/reuniones') === 0 ? 'active' : '' ?>" href="/admin/reuniones">
                                <i class="fa-solid fa-calendar-days text-warning"></i>
                                <span>Programación Reuniones</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/notificaciones') === 0 ? 'active' : '' ?>" href="/admin/notificaciones">
                                <i class="fa-solid fa-bell text-danger"></i>
                                <span>Notificaciones Push</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/socios') === 0 ? 'active' : '' ?>" href="/admin/socios">
                                <i class="fa-solid fa-users-gear text-primary"></i>
                                <span>Gestión Socios</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/inyecciones') === 0 ? 'active' : '' ?>" href="/admin/inyecciones">
                                <i class="fa-solid fa-chart-line text-success"></i>
                                <span>Inyecciones Capital</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($role === 'Presidente' && $activeMode === 'Presidente'): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/historico') === 0 ? 'active' : '' ?>" href="/admin/historico">
                                <i class="fa-solid fa-clock-rotate-left text-warning"></i>
                                <span>Histórico de Años</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <li class="nav-header">Mi Cuenta</li>
                <li>
                    <a class="nav-link <?= strpos($currentUri, '/socio/dashboard') === 0 ? 'active' : '' ?>" href="/socio/dashboard">
                        <i class="fa-solid fa-chart-line text-primary"></i>
                        <span>Mi Información</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-user-footer mt-auto">
                <div class="nt-user">
                    <div class="nt-avatar"><i class="fa-solid fa-user"></i></div>
                    <div class="overflow-hidden">
                        <div class="nt-user__name text-truncate"><?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?></div>
                        <small class="nt-role"><?= htmlspecialchars($_SESSION['usuario']['rol_nombre']) ?></small>
                    </div>
                </div>
                <div class="nt-user-actions">
                    <button type="button" class="btn nt-action" data-bs-toggle="modal" data-bs-target="#modalEditarMiPerfil">
                        <i class="fa-solid fa-user-pen"></i><span>Perfil</span>
                    </button>
                    <a href="/logout" class="btn nt-action nt-action--danger">
                        <i class="fa-solid fa-right-from-bracket"></i><span>Salir</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Mis Datos Personales (Socio) -->
    <div class="modal fade" id="modalEditarMiPerfil" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header nt-modal-head nt-modal-head--light">
                    <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-user-pen me-2 text-primary"></i>Editar Mis Datos Personales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form action="/socio/actualizar-mi-perfil" method="POST">
                    <div class="modal-body p-4 text-start text-dark">
                        <div class="mb-3">
                            <label for="my_nombre_completo" class="form-label fw-semibold fs-7">Nombre Completo</label>
                            <input type="text" name="nombre_completo" id="my_nombre_completo" class="form-control fw-bold" value="<?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?>" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="my_telefono" class="form-label fw-semibold fs-7">Teléfono de Contacto</label>
                                <input type="text" name="telefono" id="my_telefono" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario']['telefono'] ?? '') ?>" placeholder="3001234567">
                            </div>
                            <div class="col-6">
                                <label for="my_fecha_nacimiento" class="form-label fw-semibold fs-7">Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" id="my_fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario']['fecha_nacimiento'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3 border-top pt-3">
                            <label for="my_password" class="form-label fw-semibold fs-7 text-muted">Cambiar Mi Contraseña (Opcional)</label>
                            <input type="password" name="password" id="my_password" class="form-control" placeholder="Dejar en blanco para conservar la actual">
                        </div>
                    </div>
                    <div class="modal-footer nt-modal-foot">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Actualizar Mis Datos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Centro de Notificaciones y Avisos (Socio / Directiva) -->
    <div class="modal fade" id="modalMisNotificaciones" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header nt-modal-head nt-modal-head--dark">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bell text-warning"></i>
                        Centro de Notificaciones y Avisos
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4 text-dark">
                    <div id="contenedorMisNotificaciones">
                        <div class="text-center py-4 text-muted">
                            <i class="fa-solid fa-spinner fa-spin me-2 fs-5"></i>Cargando notificaciones...
                        </div>
                    </div>
                </div>
                <div class="modal-footer nt-modal-foot">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Notificación de Pagos Recibidos y Saldo Acreditado -->
    <div class="modal fade" id="modalNotificacionPagosRecibidos" tabindex="-1" aria-labelledby="modalNotificacionPagosLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 overflow-hidden">
                <!-- Encabezado del comprobante -->
                <div class="modal-header nt-receipt__head">
                    <div class="d-flex align-items-center gap-3">
                        <div class="nt-receipt__icon">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <span class="nt-receipt__tag">
                                <i class="fa-solid fa-circle-check"></i> Comprobante Oficial de Ingreso
                            </span>
                            <h4 class="font-outfit fw-bold nt-receipt__title m-0" id="modalNotificacionPagosLabel">
                                ¡Nuevo Saldo Acreditado a tu Cuenta!
                            </h4>
                            <p class="nt-receipt__sub">La administración ha registrado y confirmado tus pagos en el sistema.</p>
                        </div>
                    </div>
                </div>

                <!-- Cuerpo del Modal -->
                <div class="modal-body nt-receipt__body">
                    <!-- Total General Pagado -->
                    <div class="nt-receipt__total">
                        <span class="nt-receipt__total-label">
                            Total Pagado y Acreditado en esta Notificación
                        </span>
                        <h2 class="font-outfit nt-receipt__amount nt-money" id="modalPagoTotalMonto">
                            $0 COP
                        </h2>
                        <p class="text-muted fs-7 mb-0" id="modalPagoSubtituloSocio">
                            Socio(a): <strong class="text-dark">...</strong>
                        </p>
                    </div>

                    <!-- Desglose de Pagos ("con todo lo que haya pagado") -->
                    <div class="mb-3">
                        <h6 class="font-outfit fw-bold text-dark d-flex align-items-center gap-2 mb-3 fs-6">
                            <i class="fa-solid fa-list-check text-primary"></i>
                            Detalle de Pagos Recibidos:
                        </h6>
                        <div id="contenedorListaPagosModal" class="d-flex flex-column gap-3">
                            <!-- Se insertan dinámicamente los comprobantes -->
                        </div>
                    </div>

                    <!-- Tarjeta de Saldo Acumulado Actualizado -->
                    <div class="nt-receipt__balance">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="nt-receipt__balance-icon">
                                    <i class="fa-solid fa-vault"></i>
                                </div>
                                <div>
                                    <span class="text-muted fs-8 d-block">Nuevo Saldo Total Ahorrado Acumulado</span>
                                    <h3 class="font-outfit nt-receipt__balance-value nt-money" id="modalPagoSaldoAhorroActual">
                                        $0 COP
                                    </h3>
                                </div>
                            </div>
                            <div class="text-sm-end">
                                <span class="nt-chip">
                                    <i class="fa-solid fa-shield-halved"></i> Fondos Seguros y Registrados
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Compartir por WhatsApp -->
                    <div class="text-center pt-2">
                        <a href="#" target="_blank" class="btn btn-outline-success rounded-pill fw-bold px-4 d-inline-flex align-items-center justify-content-center" style="min-height:44px" id="btnCompartirPagoWa">
                            <i class="fa-brands fa-whatsapp me-2 fs-6"></i>Compartir Comprobante por WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Footer con Botón de Confirmación -->
                <div class="modal-footer nt-receipt__foot">
                    <button type="button" class="btn btn-primary btn-lg rounded-pill fw-bold w-100 font-outfit" id="btnAceptarComprobantePago">
                        <i class="fa-solid fa-circle-check me-2"></i>¡Entendido, Confirmar Recibido!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnBells = document.querySelectorAll('.btnAbrirNotificaciones');
        btnBells.forEach(btn => {
            btn.addEventListener('click', cargarMisNotificaciones);
        });

        function cargarMisNotificaciones() {
            const cont = document.getElementById('contenedorMisNotificaciones');
            if (!cont) return;
            cont.innerHTML = '<div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2 fs-5"></i>Cargando notificaciones...</div>';

            fetch('/socio/notificaciones-json')
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.notificaciones || data.notificaciones.length === 0) {
                        cont.innerHTML = `
                            <div class="text-center py-4 text-muted">
                                <i class="fa-solid fa-bell-slash fs-2 d-block mb-2 text-secondary"></i>
                                Aún no se han emitido notificaciones generales o personales.
                            </div>`;
                        return;
                    }

                    let html = '<div class="list-group list-group-flush">';
                    data.notificaciones.forEach(n => {
                        const fecha = n.fecha_envio ? n.fecha_envio.substring(0, 16) : '';
                        const textWa = `📢 *INFONATILLERA - NOTIFICACIÓN*\n\n📌 *${n.titulo}*\n${n.mensaje}\n\n🌐 *Ingresa a la plataforma aquí:*\nhttps://natillera.skylinedev.top/`;
                        const urlWa = `https://api.whatsapp.com/send?text=${encodeURIComponent(textWa)}`;

                        html += `
                            <div class="list-group-item p-3 border-0 bg-light rounded-3 mb-2 shadow-sm">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="fw-bold font-outfit text-dark m-0 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-bullhorn text-warning"></i>
                                        ${n.titulo}
                                    </h6>
                                    <small class="text-muted fs-8"><i class="fa-solid fa-clock me-1"></i>${fecha}</small>
                                </div>
                                <p class="text-secondary fs-7 mb-2">${n.mensaje}</p>
                                <div class="d-flex align-items-center justify-content-between fs-8">
                                    <span class="text-muted">Emitido por: <strong>${n.remitente_nombre || 'Directiva'}</strong></span>
                                    <a href="${urlWa}" target="_blank" class="btn btn-xs btn-outline-success rounded-pill fw-bold">
                                        <i class="fa-brands fa-whatsapp me-1"></i>Compartir por WhatsApp
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    cont.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    cont.innerHTML = '<div class="text-center py-4 text-danger">No se pudieron cargar las notificaciones.</div>';
                });
        }

        function formatMoney(amount) {
            return '$' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' COP';
        }

        // Consultar comprobantes de pago pendientes de mostrar al socio
        fetch('/socio/pagos-pendientes-modal')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.pagos && data.pagos.length > 0) {
                    mostrarModalComprobantePagos(data);
                } else {
                    verificarNotificacionesGeneralesPendientes();
                }
            })
            .catch(err => {
                console.error('Error al consultar comprobantes de pago:', err);
                verificarNotificacionesGeneralesPendientes();
            });

        function mostrarModalComprobantePagos(data) {
            const modalEl = document.getElementById('modalNotificacionPagosRecibidos');
            if (!modalEl) return;

            document.getElementById('modalPagoTotalMonto').textContent = formatMoney(data.total_abonado_general);
            document.getElementById('modalPagoSubtituloSocio').innerHTML = `Socio(a): <strong class="text-dark">${data.socio_nombre}</strong> (C.C. ${data.socio_cedula})`;
            document.getElementById('modalPagoSaldoAhorroActual').textContent = formatMoney(data.saldo_ahorro_actual);

            const cont = document.getElementById('contenedorListaPagosModal');
            let itemsHtml = '';
            let waDetalle = '';

            data.pagos.forEach((p) => {
                const fecha = p.fecha_pago ? p.fecha_pago.substring(0, 16) : '';
                const d = p.detalles || {};
                let badgeTipo = '<span class="badge bg-primary rounded-pill">Cuota de Reunión</span>';
                let badgeIcon = 'fa-calendar-check';

                if (p.tipo_pago === 'ANULACION_24H') {
                    badgeTipo = '<span class="badge bg-warning text-dark rounded-pill">Regla 24h (Sin Interés)</span>';
                    badgeIcon = 'fa-clock-rotate-left';
                } else if (p.tipo_pago === 'ABONO_PRESTAMO') {
                    badgeTipo = '<span class="badge bg-info text-dark rounded-pill">Abono a Préstamo</span>';
                    badgeIcon = 'fa-file-invoice-dollar';
                } else if (p.tipo_pago === 'ABONO_ACTIVIDAD') {
                    badgeTipo = '<span class="badge bg-secondary rounded-pill">Actividad Comunitaria</span>';
                    badgeIcon = 'fa-utensils';
                }

                let desgloseHtml = '';
                if (p.tipo_pago === 'CUOTA_REUNION' || p.tipo_pago === 'ANULACION_24H') {
                    desgloseHtml = `
                        <div class="row g-2 mt-2 pt-2 border-top fs-8 text-secondary">
                            <div class="col-6 col-sm-3">
                                <span class="d-block text-muted">Ahorro Cuota:</span>
                                <strong class="text-dark">${formatMoney(parseFloat(p.monto_cuota) || 0)}</strong>
                            </div>
                            ${(parseFloat(p.monto_aporte_ronda) > 0 || parseFloat(p.monto_aporte_rifa) > 0) ? `
                            <div class="col-6 col-sm-3">
                                <span class="d-block text-muted">Pasarela (Ronda/Rifa):</span>
                                <strong class="text-dark">${formatMoney((parseFloat(p.monto_aporte_ronda)||0) + (parseFloat(p.monto_aporte_rifa)||0))}</strong>
                            </div>` : ''}
                            ${parseFloat(p.monto_ahorro_extra) > 0 ? `
                            <div class="col-6 col-sm-3">
                                <span class="d-block text-muted">Ahorro Extra:</span>
                                <strong class="text-success">${formatMoney(p.monto_ahorro_extra)}</strong>
                            </div>` : ''}
                            <div class="col-6 col-sm-3">
                                <span class="d-block text-muted">Total Acreditado:</span>
                                <strong class="text-primary font-outfit fs-7">${formatMoney(p.monto_total)}</strong>
                            </div>
                        </div>
                    `;
                    waDetalle += `📌 *${p.titulo}*\n- Monto: ${formatMoney(p.monto_total)}\n`;
                } else if (p.tipo_pago === 'ABONO_PRESTAMO') {
                    desgloseHtml = `
                        <div class="row g-2 mt-2 pt-2 border-top fs-8 text-secondary">
                            <div class="col-6">
                                <span class="d-block text-muted">Abono Capital:</span>
                                <strong class="text-success">${formatMoney(d.capital_pagado || 0)}</strong>
                            </div>
                            <div class="col-6">
                                <span class="d-block text-muted">Abono Interés:</span>
                                <strong class="text-warning">${formatMoney(d.interes_pagado || 0)}</strong>
                            </div>
                        </div>
                    `;
                    waDetalle += `📌 *${p.titulo}*\n- Capital: ${formatMoney(d.capital_pagado || 0)} | Interés: ${formatMoney(d.interes_pagado || 0)}\n`;
                } else {
                    desgloseHtml = `
                        <div class="mt-2 pt-2 border-top fs-8 text-secondary">
                            <span>${p.concepto}</span>
                        </div>
                    `;
                    waDetalle += `📌 *${p.titulo}*\n- Monto: ${formatMoney(p.monto_total)}\n`;
                }

                itemsHtml += `
                    <div class="card border-0 bg-white rounded-3 p-3 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid ${badgeIcon} text-primary fs-6"></i>
                                <h6 class="font-outfit fw-bold text-dark m-0">${p.titulo}</h6>
                            </div>
                            ${badgeTipo}
                        </div>
                        <div class="d-flex align-items-center justify-content-between text-muted fs-8">
                            <span><i class="fa-solid fa-user-check text-primary me-1"></i>Registrado por: <strong>${p.registrado_por_nombre || 'Directiva'}</strong></span>
                            <span><i class="fa-regular fa-clock me-1"></i>${fecha}</span>
                        </div>
                        ${desgloseHtml}
                    </div>
                `;
            });

            cont.innerHTML = itemsHtml;

            // Formatear texto para WhatsApp
            const waText = `🧾 *INFONATILLERA - COMPROBANTE DE PAGO OFICIAL*\n\n` +
                `👤 *Socio:* ${data.socio_nombre} (C.C. ${data.socio_cedula})\n` +
                `💰 *Total Acreditado:* ${formatMoney(data.total_abonado_general)}\n\n` +
                `📋 *Detalle de Pagos:*\n${waDetalle}\n` +
                `🏦 *Nuevo Saldo Total Ahorrado:* ${formatMoney(data.saldo_ahorro_actual)}\n\n` +
                `🌐 *Verificado en InfoNatillera:*\nhttps://natillera.skylinedev.top/`;
            const btnWa = document.getElementById('btnCompartirPagoWa');
            if (btnWa) {
                btnWa.href = `https://api.whatsapp.com/send?text=${encodeURIComponent(waText)}`;
            }

            const bsModal = new bootstrap.Modal(modalEl, {
                backdrop: 'static',
                keyboard: false
            });
            bsModal.show();

            const btnAceptar = document.getElementById('btnAceptarComprobantePago');
            if (btnAceptar) {
                btnAceptar.onclick = function() {
                    btnAceptar.disabled = true;
                    btnAceptar.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando confirmación...';

                    const ids = data.pagos.map(p => p.id);
                    fetch('/socio/marcar-pagos-leidos', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids: ids })
                    })
                    .then(r => r.json())
                    .then(() => {
                        bsModal.hide();
                        verificarNotificacionesGeneralesPendientes();
                    })
                    .catch(err => {
                        console.error(err);
                        bsModal.hide();
                        verificarNotificacionesGeneralesPendientes();
                    });
                };
            }
        }

        // Auto-Popup SweetAlert2 con temporizador de 30 segundos para notificaciones pendientes al ingresar
        function verificarNotificacionesGeneralesPendientes() {
            fetch('/socio/notificaciones-pendientes-json')
                .then(r => r.json())
                .then(async data => {
                    if (data.success && data.pendientes && data.pendientes.length > 0) {
                        for (const notif of data.pendientes) {
                            const textWa = `📢 *INFONATILLERA - NOTIFICACIÓN*\n\n📌 *${notif.titulo}*\n${notif.mensaje}\n\n🌐 *Ingresa a la plataforma aquí:*\nhttps://natillera.skylinedev.top/`;
                            const urlWa = `https://api.whatsapp.com/send?text=${encodeURIComponent(textWa)}`;

                            await Swal.fire({
                                title: `📢 ${notif.titulo}`,
                                html: `
                                    <div class="text-start p-2">
                                        <p class="fs-6 text-dark mb-3">${notif.mensaje}</p>
                                        <hr class="my-2">
                                        <div class="d-flex align-items-center justify-content-between fs-7 text-muted">
                                            <span>Emitido por: <strong>${notif.remitente_nombre || 'Directiva'}</strong></span>
                                            <a href="${urlWa}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill fw-bold">
                                                <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp
                                            </a>
                                        </div>
                                    </div>
                                `,
                                icon: 'info',
                                timer: 30000,
                                timerProgressBar: true,
                                showConfirmButton: true,
                                confirmButtonText: 'Entendido / Cerrar (30s)',
                                confirmButtonColor: '#198754',
                                allowOutsideClick: false
                            });

                            const formData = new FormData();
                            formData.append('notificacion_id', notif.id);
                            fetch('/socio/notificacion-marcar-leida', { method: 'POST', body: formData });
                        }
                    }
                })
                .catch(err => console.error(err));
        }
    });
    </script>
<?php endif; ?>

<!-- Contenedor Principal de la App (Offset para Sidebar en Desktop) -->
<div class="<?= isset($_SESSION['usuario']) ? 'app-main-wrapper' : 'min-vh-100 d-flex flex-column' ?>">
    <main class="flex-grow-1 py-4">
        <div class="container-fluid px-3 px-lg-4">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show nt-flash mb-4" role="alert">
                    <i class="fa-solid fa-circle-check"></i><span><?= htmlspecialchars($_SESSION['success']) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show nt-flash mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($_SESSION['error']) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
