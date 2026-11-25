<?php
// app/Controllers/HistoricoController.php
// Historico de anos (solo Presidente en modo Presidente) y orden de socios.

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Historico.php';
require_once __DIR__ . '/../Models/Usuario.php';

class HistoricoController extends Controller {

    /** Solo el rol Presidente y solo mientras esta en modo Presidente. */
    private function exigirPresidente() {
        $user = $this->requireAuth();
        $rol = isset($_SESSION['usuario']['rol_nombre']) ? $_SESSION['usuario']['rol_nombre'] : '';
        $modo = isset($_SESSION['active_mode']) ? $_SESSION['active_mode'] : $rol;
        if ($rol !== 'Presidente' || $modo !== 'Presidente') {
            $_SESSION['error'] = 'El historico de anos es exclusivo del Presidente en modo Presidente.';
            $this->redirect('/socio/dashboard');
        }
        return $user;
    }

    private function exigirPost() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/admin/historico');
        }
    }

    /** CONFIRMAR escrito tal cual (se ignoran espacios en los extremos). */
    private function confirmado() {
        return trim(isset($_POST['confirmar']) ? (string)$_POST['confirmar'] : '') === 'CONFIRMAR';
    }

    public function index() {
        $user = $this->exigirPresidente();
        $historico = new Historico();

        $hoy = getdate();
        $anioInicio = ((int)$hoy['mon'] >= 12) ? (int)$hoy['year'] : (int)$hoy['year'] - 1;
        $this->render('admin/historico', array(
            'userRole' => $user['rol_nombre'],
            'anios' => $historico->listar(),
            'actual' => $historico->getActual(),
            'conteos' => $historico->conteosActuales(),
            'socios' => $historico->getSociosParaOrden(),
            'prestamosActivos' => $historico->prestamosActivos(),
            'nombreSugerido' => 'Dic ' . $anioInicio . ' - Nov ' . ($anioInicio + 1),
            'fechaSugerida' => date('Y-m-d'),
        ));
    }

    public function guardar() {
        $user = $this->exigirPresidente();
        $this->exigirPost();
        try {
            $r = (new Historico())->guardarActual(isset($_POST['nombre']) ? $_POST['nombre'] : '', (int)$user['id']);
            $_SESSION['success'] = ($r['creado'] ? 'Se creo el ano "' : 'Se guardo el ano "') . $r['nombre'] . '" con '
                . $r['conteos']['natillera_reuniones'] . ' reuniones, ' . $r['conteos']['natillera_prestamos'] . ' prestamos y '
                . $r['conteos']['natillera_ahorros_cuotas'] . ' cuotas.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (Throwable $e) {
            $_SESSION['error'] = 'No se pudo guardar el ano: ' . $e->getMessage();
        }
        $this->redirect('/admin/historico');
    }

    public function cargar() {
        $user = $this->exigirPresidente();
        $this->exigirPost();
        if (!$this->confirmado()) {
            $_SESSION['error'] = 'Para cargar otro ano escribe CONFIRMAR en mayusculas. No se hizo ningun cambio.';
            $this->redirect('/admin/historico');
        }
        try {
            $r = (new Historico())->cargar((int)(isset($_POST['id']) ? $_POST['id'] : 0), (int)$user['id']);
            $_SESSION['success'] = 'Ano "' . $r['nombre'] . '" cargado. El ano anterior quedo guardado y se hizo un respaldo automatico.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (Throwable $e) {
            $_SESSION['error'] = 'No se pudo cargar el ano; no se cambio nada. Detalle: ' . $e->getMessage();
        }
        $this->redirect('/admin/historico');
    }

    public function crear() {
        $user = $this->exigirPresidente();
        $this->exigirPost();
        if (!$this->confirmado()) {
            $_SESSION['error'] = 'Para crear el ano nuevo escribe CONFIRMAR en mayusculas. No se hizo ningun cambio.';
            $this->redirect('/admin/historico');
        }
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $fecha = !empty($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : date('Y-m-d');
        try {
            $historico = new Historico();
            $r = $historico->crearAnioNuevo(array(
                'nombre' => $nombre,
                'fecha_inicio' => $fecha,
                'orden' => isset($_POST['orden']) && is_array($_POST['orden']) ? $_POST['orden'] : array(),
                'continuan' => isset($_POST['continuan']) && is_array($_POST['continuan']) ? $_POST['continuan'] : array(),
                'pasar_prestamos' => !empty($_POST['pasar_prestamos']),
            ), (int)$user['id']);

            $msg = 'Ano "' . $r['nombre'] . '" creado en blanco con ' . $r['socios'] . ' socios.';
            if ($r['prestamos_arrastrados'] > 0) {
                $msg .= ' Pasaron ' . $r['prestamos_arrastrados'] . ' prestamos activos como saldo inicial.';
            }
            // Las 26 reuniones del ano nuevo las genera Reunion::generarCiclo (si ya existe en esta instalacion)
            $ciclo = $this->generarCiclo($fecha, $r['nombre'], (int)$user['id']);
            if ($ciclo === true) {
                $historico->guardarActual(null, (int)$user['id']); // la foto del ano nuevo incluye sus reuniones
                $msg .= ' Se generaron sus reuniones.';
            } elseif ($ciclo === false) {
                $msg .= ' Recuerda crear las reuniones del ano nuevo en Reuniones.';
            } else {
                $msg .= ' Hubo un problema al generar las reuniones (' . $ciclo . '): crealas en Reuniones.';
            }
            $_SESSION['success'] = $msg;
        } catch (InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (Throwable $e) {
            $_SESSION['error'] = 'No se pudo crear el ano nuevo; no se cambio nada. Detalle: ' . $e->getMessage();
        }
        $this->redirect('/admin/historico');
    }

    /**
     * Llama a Reunion::generarCiclo si existe (lo implementa otro modulo).
     * @return bool|string true = generado, false = no existe todavia, string = mensaje de error
     */
    private function generarCiclo($fechaInicio, $nombre, $usuarioId) {
        try {
            require_once __DIR__ . '/../Models/Reunion.php';
            if (!class_exists('Reunion') || !method_exists('Reunion', 'generarCiclo')) {
                return false;
            }
            $rm = new ReflectionMethod('Reunion', 'generarCiclo');
            // Reunion::generarCiclo(string $fechaPrimeraReunion, ...): solo se le pasa la fecha de la primera reunion
            $args = array_slice(array($fechaInicio), 0, $rm->getNumberOfParameters());
            $obj = $rm->isStatic() ? null : new Reunion();
            $rm->invokeArgs($obj, $args);
            return true;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    public function recuperar() {
        $user = $this->exigirPresidente();
        $this->exigirPost();
        try {
            $r = (new Historico())->recuperarRespaldo((int)(isset($_POST['id']) ? $_POST['id'] : 0), (int)$user['id']);
            $_SESSION['success'] = 'El respaldo quedo disponible como el ano "' . $r['nombre'] . '". Usa "Cargar" para restaurarlo.';
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        $this->redirect('/admin/historico');
    }

    public function descargar() {
        $this->exigirPresidente();
        try {
            list($nombre, $json) = (new Historico())->descargar((int)(isset($_GET['id']) ? $_GET['id'] : 0));
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/historico');
            return;
        }
        $slug = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $nombre), '_');
        if ($slug === '') {
            $slug = 'historico';
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="natillera_' . $slug . '_' . date('Ymd') . '.json"');
        header('Content-Length: ' . strlen($json));
        header('X-Content-Type-Options: nosniff');
        echo $json;
        exit;
    }

    /** POST /admin/socios/ordenar: guarda solo el orden de los socios (no cambia estados ni datos). */
    public function ordenarSocios() {
        $this->requireRole(array('Presidente', 'Secretaria General'));
        $this->exigirPost();
        $volver = (isset($_POST['volver']) && $_POST['volver'] === 'socios') ? '/admin/socios' : '/admin/historico';

        $ids = array();
        if (isset($_POST['orden']) && is_array($_POST['orden'])) {
            $ids = $_POST['orden'];
        } elseif (!empty($_POST['orden_csv'])) {
            $ids = explode(',', (string)$_POST['orden_csv']);
        }
        if (empty($ids)) {
            $_SESSION['error'] = 'No llego ningun orden para guardar.';
            $this->redirect($volver);
        }
        try {
            $n = (new Usuario())->guardarOrden($ids);
            $_SESSION['success'] = 'Orden de socios guardado (' . $n . ' socios).';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'No se pudo guardar el orden: ' . $e->getMessage();
        }
        $this->redirect($volver);
    }
}
