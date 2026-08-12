<?php
// public/index.php - Front Controller Central

if (php_sapi_name() === 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

session_start();

// Autoload simple de clases en core, config y app
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../core/',
        __DIR__ . '/../config/',
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Models/'
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Inicializar Router
$router = new Router();

// Rutas Públicas / Autenticación
$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/toggle-mode', [AuthController::class, 'toggleMode']);

// Rutas Admin - Llamado a Lista
$router->get('/admin/llamado-lista', [LlamadoListaController::class, 'index']);
$router->post('/admin/llamado-lista/guardar-batch', [LlamadoListaController::class, 'guardarBatch']);
$router->post('/admin/llamado-lista/anular-24h', [LlamadoListaController::class, 'anularAutoprestamo24H']);

// Rutas Admin - Préstamos
$router->get('/admin/prestamos', [PrestamoController::class, 'index']);
$router->post('/admin/prestamos/guardar', [PrestamoController::class, 'guardar']);
$router->post('/admin/prestamos/actualizar', [PrestamoController::class, 'actualizar']);
$router->post('/admin/prestamos/abono', [PrestamoController::class, 'abono']);
$router->get('/admin/prestamos/abonos-json', [PrestamoController::class, 'obtenerAbonos']);
$router->post('/admin/prestamos/abono/actualizar', [PrestamoController::class, 'actualizarAbono']);
$router->post('/admin/prestamos/abono/eliminar', [PrestamoController::class, 'eliminarAbono']);
$router->post('/admin/prestamos/actualizar-tope', [PrestamoController::class, 'actualizarTope']);

// Rutas Admin - Actividades
$router->get('/admin/actividades', [ActividadController::class, 'index']);
$router->post('/admin/actividades/guardar', [ActividadController::class, 'guardar']);
$router->get('/admin/actividades/participantes-json', [ActividadController::class, 'participantesJson']);
$router->post('/admin/actividades/pago/actualizar', [ActividadController::class, 'actualizarPago']);
$router->post('/admin/actividades/abono/guardar', [ActividadController::class, 'registrarAbono']);
$router->post('/admin/actividades/abono/eliminar', [ActividadController::class, 'eliminarAbono']);

// Rutas Admin - Entregas de Rondas y Rifas (Firma y Foto)
$router->get('/admin/entregas', [EntregaController::class, 'index']);
$router->post('/admin/entregas/guardar', [EntregaController::class, 'guardar']);
$router->post('/admin/entregas/eliminar', [EntregaController::class, 'eliminar']);
$router->get('/admin/entregas/socios-pendientes-json', [EntregaController::class, 'sociosPendientesJson']);

// Rutas Admin - Notificaciones Push
$router->get('/admin/notificaciones', [NotificacionController::class, 'index']);
$router->post('/admin/notificaciones/enviar', [NotificacionController::class, 'enviar']);
$router->post('/admin/notificaciones/suscribir', [NotificacionController::class, 'suscribir']);
$router->get('/socio/notificaciones-json', [NotificacionController::class, 'notificacionesJson']);

// Rutas Admin - Gestión de Reuniones y Cuotas
$router->get('/admin/reuniones', [ReunionController::class, 'index']);
$router->post('/admin/reuniones/crear', [ReunionController::class, 'crear']);
$router->post('/admin/reuniones/actualizar', [ReunionController::class, 'actualizar']);

// Rutas Admin - Gestión de Socios
$router->get('/admin/socios', [SocioController::class, 'gestionarSocios']);
$router->get('/admin/socios/expediente-json', [SocioController::class, 'expedienteJson']);
$router->post('/admin/socios/crear', [SocioController::class, 'crearSocio']);
$router->post('/admin/socios/actualizar', [SocioController::class, 'actualizarSocio']);

// Rutas Admin - Inyecciones de Capital
$router->get('/admin/inyecciones', [InyeccionController::class, 'index']);
$router->post('/admin/inyecciones/crear', [InyeccionController::class, 'crear']);
$router->post('/admin/inyecciones/retirar', [InyeccionController::class, 'retirar']);

// Rutas Admin - Arqueo y Cierre Financiero por Reunión
$router->get('/admin/cierre-reunion', [CierreController::class, 'index']);
$router->post('/admin/cierre-reunion/cerrar', [CierreController::class, 'cerrar']);

// Rutas Admin - Transferencias entre Cajas
$router->get('/admin/transferencias-cajas', [TransferenciaCajaController::class, 'index']);
$router->post('/admin/transferencias-cajas/crear', [TransferenciaCajaController::class, 'crear']);

// Rutas Socio
$router->get('/socio/dashboard', [SocioController::class, 'dashboard']);
$router->get('/socio/liquidacion-anual', [SocioController::class, 'liquidacionAnual']);
$router->post('/socio/actualizar-mi-perfil', [SocioController::class, 'actualizarMiPerfil']);

// Despachar la Petición
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($uri, $method);
