<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Natillera Comunitaria - Software de Control</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/app.css">
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
            <a class="d-flex align-items-center gap-2 fw-bold text-gradient-gold text-decoration-none" href="/socio/dashboard">
                <i class="fa-solid fa-coins fs-4 text-warning"></i>
                <span class="font-outfit fs-5">InfoNatillera</span>
            </a>
        </div>

        <?php if ($role === 'Presidente'): ?>
            <div class="px-3 py-3 border-bottom border-white border-opacity-10 bg-white bg-opacity-5">
                <form action="/toggle-mode" method="POST" class="m-0">
                    <button type="submit" class="btn btn-sm btn-outline-warning w-100 rounded-pill d-flex align-items-center justify-content-center gap-2 fs-7 fw-semibold">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>Modo: <strong class="text-uppercase"><?= htmlspecialchars($activeMode) ?></strong></span>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <ul class="sidebar-nav">
            <?php if ($isAdminAccess): ?>
                <li class="nav-header">Módulos Directiva</li>

                <?php if (in_array($role, ['Presidente', 'Tesorera'])): ?>
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
                <?php endif; ?>

                <?php if (in_array($role, ['Presidente', 'Secretaria Actividades'])): ?>
                    <li>
                        <a class="nav-link <?= strpos($currentUri, '/admin/actividades') === 0 ? 'active' : '' ?>" href="/admin/actividades">
                            <i class="fa-solid fa-utensils text-info"></i>
                            <span>Actividades (Tamales)</span>
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
            <?php endif; ?>

            <li class="nav-header">Mi Cuenta</li>
            <li>
                <a class="nav-link <?= strpos($currentUri, '/socio/dashboard') === 0 ? 'active' : '' ?>" href="/socio/dashboard">
                    <i class="fa-solid fa-chart-line text-primary"></i>
                    <span>Mi Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-user-footer">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:38px; height:38px;">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold font-outfit text-white text-truncate fs-7"><?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?></div>
                    <small class="badge bg-secondary text-uppercase fs-8"><?= htmlspecialchars($_SESSION['usuario']['rol_nombre']) ?></small>
                </div>
            </div>
            <div class="d-flex gap-1 mt-2">
                <button type="button" class="btn btn-xs btn-outline-light w-50 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalEditarMiPerfil">
                    <i class="fa-solid fa-user-pen me-1"></i>Perfil
                </button>
                <a href="/logout" class="btn btn-xs btn-outline-danger w-50 rounded-pill text-decoration-none text-center">
                    <i class="fa-solid fa-right-from-bracket me-1"></i>Salir
                </a>
            </div>
        </div>
    </aside>

    <!-- Header Móvil (< 992px) -->
    <header class="navbar navbar-dark bg-glass-dark d-lg-none sticky-top shadow-sm py-2 px-3">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-gradient-gold text-decoration-none" href="/socio/dashboard">
            <i class="fa-solid fa-coins fs-4 text-warning"></i>
            <span class="font-outfit fs-5">InfoNatillera</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
    </header>

    <!-- Offcanvas Mobile Sidebar -->
    <div class="offcanvas offcanvas-start bg-dark text-white d-lg-none" tabindex="-1" id="offcanvasSidebar">
        <div class="offcanvas-header border-bottom border-white border-opacity-10">
            <h5 class="offcanvas-title font-outfit fw-bold text-gradient-gold"><i class="fa-solid fa-coins me-2 text-warning"></i>InfoNatillera</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            <?php if ($role === 'Presidente'): ?>
                <div class="p-3 border-bottom border-white border-opacity-10 bg-white bg-opacity-5">
                    <form action="/toggle-mode" method="POST" class="m-0">
                        <button type="submit" class="btn btn-sm btn-outline-warning w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span>Modo: <strong class="text-uppercase"><?= htmlspecialchars($activeMode) ?></strong></span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <ul class="sidebar-nav">
                <?php if ($isAdminAccess): ?>
                    <li class="nav-header">Módulos Directiva</li>

                    <?php if (in_array($role, ['Presidente', 'Tesorera'])): ?>
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
                    <?php endif; ?>

                    <?php if (in_array($role, ['Presidente', 'Secretaria Actividades'])): ?>
                        <li>
                            <a class="nav-link <?= strpos($currentUri, '/admin/actividades') === 0 ? 'active' : '' ?>" href="/admin/actividades">
                                <i class="fa-solid fa-utensils text-info"></i>
                                <span>Actividades (Tamales)</span>
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
                <?php endif; ?>

                <li class="nav-header">Mi Cuenta</li>
                <li>
                    <a class="nav-link <?= strpos($currentUri, '/socio/dashboard') === 0 ? 'active' : '' ?>" href="/socio/dashboard">
                        <i class="fa-solid fa-chart-line text-primary"></i>
                        <span>Mi Dashboard</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-user-footer mt-auto">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:38px; height:38px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="fw-bold font-outfit text-white text-truncate fs-7"><?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?></div>
                        <small class="badge bg-secondary text-uppercase fs-8"><?= htmlspecialchars($_SESSION['usuario']['rol_nombre']) ?></small>
                    </div>
                </div>
                <div class="d-flex gap-1 mt-2">
                    <button type="button" class="btn btn-xs btn-outline-light w-50 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalEditarMiPerfil">
                        <i class="fa-solid fa-user-pen me-1"></i>Perfil
                    </button>
                    <a href="/logout" class="btn btn-xs btn-outline-danger w-50 rounded-pill text-decoration-none text-center">
                        <i class="fa-solid fa-right-from-bracket me-1"></i>Salir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Mis Datos Personales (Socio) -->
    <div class="modal fade" id="modalEditarMiPerfil" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-info text-white border-0">
                    <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Editar Mis Datos Personales</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info text-white rounded-pill fw-bold px-4">Actualizar Mis Datos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Contenedor Principal de la App (Offset para Sidebar en Desktop) -->
<div class="<?= isset($_SESSION['usuario']) ? 'app-main-wrapper' : 'min-vh-100 d-flex flex-column' ?>">
    <main class="flex-grow-1 py-4">
        <div class="container-fluid px-3 px-lg-4">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
