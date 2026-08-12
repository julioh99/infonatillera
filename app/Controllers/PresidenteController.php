<?php
// app/Controllers/PresidenteController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/PresidenteDashboard.php';

class PresidenteController extends Controller {

    public function dashboard(): void {
        $this->requireRole(['Presidente']);

        $presiModel = new PresidenteDashboard();

        $resumen = $presiModel->getResumenFinancieroGlobal();
        $sociosSinPrestamos = $presiModel->getSociosSinPrestamos();
        $sociosSinActividades = $presiModel->getSociosSinActividades();
        $sociosCuotasPendientes = $presiModel->getSociosConCuotasPendientes();

        $this->render('admin/dashboard_presidente', [
            'resumen' => $resumen,
            'sociosSinPrestamos' => $sociosSinPrestamos,
            'sociosSinActividades' => $sociosSinActividades,
            'sociosCuotasPendientes' => $sociosCuotasPendientes
        ]);
    }
}
