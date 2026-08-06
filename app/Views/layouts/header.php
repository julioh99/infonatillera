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
<body class="bg-light-gradient min-vh-100 d-flex flex-column">

<?php if (isset($_SESSION['usuario'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-glass-dark sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-gradient-gold" href="/socio/dashboard">
                <i class="fa-solid fa-coins fs-4 text-warning"></i>
                <span class="font-outfit fs-5">InfoNatillera</span>
            </a>
            
            <div class="d-flex align-items-center gap-2 order-lg-last ms-auto ms-lg-0">
                <!-- Dual Mode Toggle para el Presidente -->
                <?php if (($_SESSION['usuario']['rol_nombre'] ?? '') === 'Presidente'): ?>
                    <form action="/toggle-mode" method="POST" class="m-0">
                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3 d-flex align-items-center gap-1 shadow-sm fs-7">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span class="d-none d-sm-inline">Modo:</span>
                            <strong class="text-uppercase"><?= htmlspecialchars($_SESSION['active_mode'] ?? 'Presidente') ?></strong>
                        </button>
                    </form>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="btn btn-sm btn-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:38px; height:38px;" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-user-gear text-light"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold text-dark font-outfit"><?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?></div>
                            <small class="badge bg-primary text-uppercase"><?= htmlspecialchars($_SESSION['usuario']['rol_nombre']) ?></small>
                        </li>
                        <li><a class="dropdown-item py-2" href="/socio/dashboard"><i class="fa-solid fa-user-circle me-2 text-primary"></i>Mi Perfil / Mi Saldo</a></li>
                        <li><button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#modalEditarMiPerfil"><i class="fa-solid fa-user-pen me-2 text-info"></i>Editar Mis Datos</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger fw-semibold" href="/logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>

                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-1">
                    <?php 
                    $role = $_SESSION['usuario']['rol_nombre'] ?? '';
                    $activeMode = $_SESSION['active_mode'] ?? $role;
                    $isAdminAccess = ($role === 'Presidente' && $activeMode === 'Presidente') || in_array($role, ['Tesorera', 'Secretaria General', 'Secretaria Actividades']);
                    ?>

                    <?php if ($isAdminAccess): ?>
                        <?php if (in_array($role, ['Presidente', 'Tesorera'])): ?>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 rounded-2 fw-medium" href="/admin/llamado-lista">
                                    <i class="fa-solid fa-clipboard-check text-success me-1"></i> Llamado a Lista
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (in_array($role, ['Presidente', 'Tesorera', 'Secretaria General'])): ?>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 rounded-2 fw-medium" href="/admin/prestamos">
                                    <i class="fa-solid fa-hand-holding-dollar text-warning me-1"></i> Préstamos
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (in_array($role, ['Presidente', 'Secretaria Actividades'])): ?>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 rounded-2 fw-medium" href="/admin/actividades">
                                    <i class="fa-solid fa-utensils text-info me-1"></i> Actividades (Tamales)
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (in_array($role, ['Presidente', 'Secretaria General'])): ?>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 rounded-2 fw-medium" href="/admin/reuniones">
                                    <i class="fa-solid fa-calendar-days text-warning me-1"></i> Programación Reuniones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 rounded-2 fw-medium" href="/admin/notificaciones">
                                    <i class="fa-solid fa-bell text-danger me-1"></i> Notificaciones Push
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 rounded-2 fw-medium" href="/admin/socios">
                                    <i class="fa-solid fa-users-gear text-primary me-1"></i> Gestión Socios
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 rounded-2 fw-medium" href="/socio/dashboard">
                            <i class="fa-solid fa-chart-line text-primary me-1"></i> Mi Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
