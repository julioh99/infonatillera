<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-utensils text-info"></i>
            Actividades Especiales (Tamales / Eventos)
        </h2>
        <p class="text-muted m-0 fs-7">Gestión contable aislada, registro de ingresos, gastos y utilidad neta.</p>
    </div>
    <div class="col-12 col-md-6 text-md-end">
        <button type="button" class="btn btn-info text-white rounded-pill fw-bold btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalNuevaActividad">
            <i class="fa-solid fa-plus me-1"></i>Nueva Actividad
        </button>
    </div>
</div>

<!-- Lista de Actividades -->
<div class="row g-4">
    <?php if (empty($actividades)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                <i class="fa-solid fa-cookie-bite fs-1 text-secondary mb-3"></i>
                <h5 class="font-outfit">No hay actividades registradas</h5>
                <p class="m-0 fs-7">Crea eventos comunitarios para registrar fondos extras repartidos entre los participantes.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($actividades as $act): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                    <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-outfit fw-bold m-0"><?= htmlspecialchars($act['nombre_actividad']) ?></h5>
                        <span class="badge bg-primary fs-7"><?= date('d/m/Y', strtotime($act['fecha_actividad'])) ?></span>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted fs-7 mb-3"><?= htmlspecialchars($act['descripcion'] ?: 'Sin descripción adicional.') ?></p>
                        
                        <div class="border rounded-3 p-3 bg-light mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fs-7 text-muted">Ingresos Totales:</span>
                                <strong class="text-success">$<?= number_format($act['ingresos_totales'], 0, ',', '.') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fs-7 text-muted">Gastos Totales:</span>
                                <strong class="text-danger">$<?= number_format($act['gastos_totales'], 0, ',', '.') ?></strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold font-outfit">Ganancia Neta:</span>
                                <strong class="fs-5 text-primary font-outfit">$<?= number_format($act['ganancia_neta'], 0, ',', '.') ?></strong>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between text-muted fs-7">
                            <span><i class="fa-solid fa-users text-info me-1"></i> Participantes: <strong><?= $act['total_participantes'] ?> socios</strong></span>
                            <span>Creado por: <?= htmlspecialchars($act['creador_nombre']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Nueva Actividad -->
<div class="modal fade" id="modalNuevaActividad" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-utensils me-2"></i>Registrar Actividad Especial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/actividades/guardar" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-8">
                            <label for="nombre_actividad" class="form-label fw-semibold fs-7">Nombre de la Actividad</label>
                            <input type="text" name="nombre_actividad" id="nombre_actividad" class="form-control" placeholder="Ej: Venta de Tamales de Junio" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="fecha_actividad" class="form-label fw-semibold fs-7">Fecha Realización</label>
                            <input type="date" name="fecha_actividad" id="fecha_actividad" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label fw-semibold fs-7">Descripción</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" rows="2" placeholder="Detalles de la actividad, proveedores..."></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label for="ingresos_totales" class="form-label fw-semibold fs-7">Ingresos Totales Recaudados (COP)</label>
                            <input type="number" step="1000" min="0" name="ingresos_totales" id="ingresos_totales" class="form-control text-success fw-bold" placeholder="0" required>
                        </div>
                        <div class="col-6">
                            <label for="gastos_totales" class="form-label fw-semibold fs-7">Gastos Totales (COP)</label>
                            <input type="number" step="1000" min="0" name="gastos_totales" id="gastos_totales" class="form-control text-danger fw-bold" placeholder="0" required>
                        </div>
                    </div>

                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold fs-7 mb-0">Socios Participantes (Para reparto equitativo de ganancias):</label>
                            <div>
                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="marcarTodosSociosAct(true)">Todos</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="marcarTodosSociosAct(false)">Ninguno</button>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-2 overflow-auto" style="max-height: 200px;">
                            <?php foreach ($socios as $s): ?>
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input chk-socio-act" type="checkbox" name="participantes[]" value="<?= $s['id'] ?>" id="part_<?= $s['id'] ?>" checked>
                                        <label class="form-check-label fs-7" for="part_<?= $s['id'] ?>">
                                            <?= htmlspecialchars($s['nombre_completo']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white rounded-pill fw-bold px-4">Liquidar Actividad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function marcarTodosSociosAct(status) {
    document.querySelectorAll('.chk-socio-act').forEach(c => c.checked = status);
}
</script>
