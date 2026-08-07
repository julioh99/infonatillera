<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-utensils text-info"></i>
            Actividades Especiales (Tamales / Eventos)
        </h2>
        <p class="text-muted m-0 fs-7">Gestión contable, asignación de cuotas individuales por socio y recaudo.</p>
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
                <p class="m-0 fs-7">Crea eventos comunitarios para registrar cuotas individuales y fondos repartibles.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($actividades as $act): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                    <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-outfit fw-bold m-0 text-truncate" style="max-width: 200px;"><?= htmlspecialchars($act['nombre_actividad']) ?></h5>
                        <span class="badge bg-primary fs-7"><?= date('d/m/Y', strtotime($act['fecha_actividad'])) ?></span>
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <p class="text-muted fs-7 mb-3"><?= htmlspecialchars($act['descripcion'] ?: 'Sin descripción adicional.') ?></p>
                        
                        <div class="border rounded-3 p-3 bg-light mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fs-7 text-muted">Cuota Referencial Socio:</span>
                                <strong class="text-primary">$<?= number_format($act['cuota_por_socio'] ?? 0, 0, ',', '.') ?></strong>
                            </div>
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

                        <div class="d-flex align-items-center justify-content-between text-muted fs-7 mb-3">
                            <span><i class="fa-solid fa-users text-info me-1"></i> <strong><?= $act['total_participantes'] ?> socios</strong></span>
                            <span>Creado por: <?= htmlspecialchars($act['creador_nombre']) ?></span>
                        </div>

                        <div class="mt-auto">
                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill w-100 fw-bold btnVerParticipantes"
                                    data-id="<?= $act['id'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalVerParticipantesActividad">
                                <i class="fa-solid fa-list-check me-1"></i>Ver Participantes y Pagos
                            </button>
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
                        <div class="col-12 col-md-4">
                            <label for="cuota_por_socio" class="form-label fw-semibold fs-7">Cuota Base Referencial (COP)</label>
                            <input type="text" name="cuota_por_socio" id="cuota_por_socio" class="form-control money-input text-primary fw-bold" placeholder="Ej: 20.000" value="20.000">
                            <small class="text-muted fs-8">Valor base para autocompletar.</small>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="ingresos_totales" class="form-label fw-semibold fs-7">Ingresos Totales Recaudados (COP)</label>
                            <input type="text" name="ingresos_totales" id="ingresos_totales" class="form-control money-input text-success fw-bold" placeholder="0" value="0" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="gastos_totales" class="form-label fw-semibold fs-7">Gastos Totales (COP)</label>
                            <input type="text" name="gastos_totales" id="gastos_totales" class="form-control money-input text-danger fw-bold" placeholder="0" value="0" required>
                        </div>
                    </div>

                    <!-- Asignación de Cuotas Individuales por Socio -->
                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <label class="form-label fw-bold font-outfit fs-7 mb-0 text-dark">
                                    <i class="fa-solid fa-users me-1 text-info"></i>Socios Participantes y Cuotas Individuales:
                                </label>
                                <span class="d-block text-muted fs-8">Asigna el valor individual a pagar por cada socio seleccionado.</span>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-xs btn-outline-info py-1 px-2 fw-semibold" onclick="aplicarCuotaBaseASocios()">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Aplicar Cuota Base
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" onclick="marcarTodosSociosAct(true)">Todos</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="marcarTodosSociosAct(false)">Ninguno</button>
                            </div>
                        </div>

                        <div class="row g-2 overflow-auto" style="max-height: 280px;">
                            <?php foreach ($socios as $s): ?>
                                <div class="col-12 col-md-6">
                                    <div class="card p-2 border shadow-none bg-white">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <div class="form-check m-0">
                                                <input class="form-check-input chk-socio-act" type="checkbox" name="participantes[]" value="<?= $s['id'] ?>" id="part_<?= $s['id'] ?>" onchange="toggleSocioCuotaInput(this, <?= $s['id'] ?>)" checked>
                                                <label class="form-check-label fs-7 fw-semibold text-dark text-truncate" for="part_<?= $s['id'] ?>" style="max-width: 170px;" title="<?= htmlspecialchars($s['nombre_completo']) ?>">
                                                    <?= htmlspecialchars($s['nombre_completo']) ?>
                                                </label>
                                            </div>
                                            <div class="input-group input-group-sm" style="width: 140px;">
                                                <span class="input-group-text">$</span>
                                                <input type="text" name="cuotas_individuales[<?= $s['id'] ?>]" id="cuota_socio_<?= $s['id'] ?>" class="form-control form-control-sm text-end fw-bold input-cuota-socio money-input" value="20.000" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white rounded-pill fw-bold px-4">Crear Actividad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver / Actualizar Participantes y Saldos de la Actividad -->
<div class="modal fade" id="modalVerParticipantesActividad" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-list-check me-2 text-warning"></i>Participantes y Recaudo de la Actividad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-dark">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <div>
                        <h6 class="m-0 fw-bold font-outfit text-primary" id="act_modal_nombre">---</h6>
                        <small class="text-muted" id="act_modal_fecha">---</small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle fs-7 mb-0">
                        <thead class="table-dark font-outfit">
                            <tr>
                                <th class="ps-3">Socio</th>
                                <th>Cuota Asignada</th>
                                <th>Monto Pagado</th>
                                <th>Saldo Pendiente</th>
                                <th>Estado</th>
                                <th class="text-end pe-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyParticipantesAct">
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando participantes...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSocioCuotaInput(chk, socioId) {
    const input = document.getElementById('cuota_socio_' + socioId);
    if (input) {
        input.disabled = !chk.checked;
        if (!chk.checked) {
            input.dataset.oldVal = input.value;
            input.value = 0;
        } else if (input.dataset.oldVal) {
            input.value = input.dataset.oldVal;
        }
    }
}

function marcarTodosSociosAct(status) {
    document.querySelectorAll('.chk-socio-act').forEach(c => {
        c.checked = status;
        const sId = c.value;
        toggleSocioCuotaInput(c, sId);
    });
}

function aplicarCuotaBaseASocios() {
    const baseVal = document.getElementById('cuota_por_socio').value || 0;
    document.querySelectorAll('.input-cuota-socio').forEach(inp => {
        if (!inp.disabled) {
            inp.value = baseVal;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Cargar Participantes de Actividad via AJAX
    const btnsVerParticipantes = document.querySelectorAll('.btnVerParticipantes');
    btnsVerParticipantes.forEach(btn => {
        btn.addEventListener('click', () => {
            const actId = btn.getAttribute('data-id');
            const tbody = document.getElementById('tbodyParticipantesAct');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando participantes...</td></tr>';

            fetch(`/admin/actividades/participantes-json?actividad_id=${actId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.actividad) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-danger">Error al cargar información.</td></tr>';
                        return;
                    }

                    document.getElementById('act_modal_nombre').innerText = data.actividad.nombre_actividad;
                    document.getElementById('act_modal_fecha').innerText = `Fecha: ${data.actividad.fecha_actividad}`;

                    if (!data.participantes || data.participantes.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">No se registraron participantes para esta actividad.</td></tr>';
                        return;
                    }

                    let html = '';
                    data.participantes.forEach(p => {
                        const cuota = parseFloat(p.cuota_asignada || 0);
                        const pagado = parseFloat(p.monto_pagado || 0);
                        const saldo = cuota - pagado;
                        const isPagado = (p.estado_pago === 'PAGADO' || saldo <= 0);
                        const abonos = p.abonos || [];
                        const numAbonos = abonos.length;

                        let abonosHtml = '';
                        if (numAbonos > 0) {
                            abonosHtml += `
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered bg-white fs-7 mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Fecha y Hora</th>
                                                <th>Monto Abonado</th>
                                                <th>Registrado Por</th>
                                                <th>Nota / Detalle</th>
                                                <th class="text-center" style="width: 50px;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            abonos.forEach(ab => {
                                const fStr = new Date(ab.fecha_abono).toLocaleString('es-CO');
                                abonosHtml += `
                                    <tr>
                                        <td>${fStr}</td>
                                        <td class="fw-bold text-success">$${new Intl.NumberFormat('es-CO').format(parseFloat(ab.monto_abono))} COP</td>
                                        <td>${ab.registrado_por_nombre || 'Sistema'}</td>
                                        <td>${ab.observacion || '-'}</td>
                                        <td class="text-center">
                                            <form action="/admin/actividades/abono/eliminar" method="POST" onsubmit="return confirm('¿Deseas eliminar este abono de $${new Intl.NumberFormat('es-CO').format(parseFloat(ab.monto_abono))}?');" class="d-inline">
                                                <input type="hidden" name="abono_id" value="${ab.id}">
                                                <button type="submit" class="btn btn-link text-danger p-0 border-0" title="Eliminar abono"><i class="fa-solid fa-trash-can"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                `;
                            });
                            abonosHtml += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        } else {
                            abonosHtml = '<div class="text-muted small py-2"><i class="fa-solid fa-info-circle me-1"></i>No hay abonos individuales registrados para este socio aún.</div>';
                        }

                        html += `
                            <tr class="align-middle">
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">${p.nombre_completo}</div>
                                    <button type="button" class="btn btn-xs btn-outline-info rounded-pill py-0 px-2 mt-1" data-bs-toggle="collapse" data-bs-target="#abonos_p_${p.id}">
                                        <i class="fa-solid fa-receipt me-1"></i>Abonos (${numAbonos})
                                    </button>
                                </td>
                                <td class="fw-bold">$${new Intl.NumberFormat('es-CO').format(cuota)}</td>
                                <td class="text-success fw-bold">$${new Intl.NumberFormat('es-CO').format(pagado)}</td>
                                <td>${saldo > 0 ? `<span class="badge bg-danger">$${new Intl.NumberFormat('es-CO').format(saldo)}</span>` : '<span class="text-muted">$0</span>'}</td>
                                <td><span class="badge bg-${isPagado ? 'success' : 'warning text-dark'}">${isPagado ? 'Al Día' : 'Pendiente'}</span></td>
                                <td class="text-end pe-3">
                                    <form action="/admin/actividades/abono/guardar" method="POST" class="d-inline-flex align-items-center gap-1">
                                        <input type="hidden" name="participante_id" value="${p.id}">
                                        <input type="number" step="1000" min="1000" name="monto_abono" placeholder="Ej: 12000" class="form-control form-control-sm text-end" style="width: 110px;" required>
                                        <button type="submit" class="btn btn-sm btn-success fw-bold px-2 py-1" title="Registrar Nuevo Abono"><i class="fa-solid fa-plus me-1"></i>Abonar</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse" id="abonos_p_${p.id}">
                                <td colspan="6" class="bg-light p-3 border-bottom">
                                    <div class="fw-semibold text-primary mb-1"><i class="fa-solid fa-clock-rotate-left me-1"></i>Historial de Pagos por Separado - ${p.nombre_completo}</div>
                                    ${abonosHtml}
                                </td>
                            </tr>
                        `;
                    });

                    tbody.innerHTML = html;
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-danger">Error de conexión al cargar la información.</td></tr>';
                });
        });
    });
});
</script>
