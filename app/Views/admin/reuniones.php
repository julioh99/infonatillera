<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-8 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-calendar-days text-warning"></i>
            Programación y Valor de Reuniones
        </h2>
        <p class="text-muted m-0 fs-7">Edición de fechas, horas, valor de cuota base ($55k, $60k, $65k) y pasarelas de Rifa/Ronda (exclusivo Presidente y Secretaria General).</p>
    <div class="col-12 col-md-4 text-md-end">
        <button type="button" class="btn btn-warning rounded-pill fw-bold btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalNuevaReunion">
            <i class="fa-solid fa-plus me-1"></i>Nueva Reunión
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-dark text-white font-outfit fs-7 text-uppercase">
                <tr>
                    <th class="ps-4">Reunión</th>
                    <th>Fecha / Hora</th>
                    <th>Valor Cuota Base</th>
                    <th>Evento / Pasarela</th>
                    <th>Premio Entregado</th>
                    <th>Ganador Sorteo</th>
                    <th>Estado</th>
                    <th class="text-end pe-4">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reuniones as $r): ?>
                    <tr>
                        <td class="ps-4">
                            <strong class="font-outfit fs-6">Reunión Nº <?= $r['numero_quincena'] ?></strong>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><i class="fa-solid fa-calendar-day text-primary me-1"></i><?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></div>
                            <small class="text-muted"><i class="fa-solid fa-clock me-1"></i><?= $r['hora_reunion'] ?></small>
                        </td>
                        <td class="fw-bold text-success font-outfit fs-6">$<?= number_format($r['valor_cuota_base'], 0, ',', '.') ?> COP</td>
                        <td>
                            <?php if ($r['tipo_evento_extra'] === 'RIFA'): ?>
                                <span class="badge bg-danger">Rifa ($150k)</span>
                            <?php elseif ($r['tipo_evento_extra'] === 'RONDA'): ?>
                                <span class="badge bg-success">Ronda ($300k)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Regular ($55k)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['monto_premio_extra'] > 0): ?>
                                <span class="fw-bold text-warning font-outfit">$<?= number_format($r['monto_premio_extra'], 0, ',', '.') ?></span>
                            <?php else: ?>
                                <span class="text-muted fs-7">$0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['ganador_nombre'])): ?>
                                <span class="badge bg-info text-dark font-outfit"><i class="fa-solid fa-trophy text-warning me-1"></i><?= htmlspecialchars($r['ganador_nombre']) ?></span>
                            <?php else: ?>
                                <span class="text-muted fs-7">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['estado'] === 'CERRADA'): ?>
                                <span class="badge bg-secondary">Cerrada</span>
                            <?php elseif ($r['estado'] === 'EN_PROCESO'): ?>
                                <span class="badge bg-warning text-dark">En Proceso</span>
                            <?php else: ?>
                                <span class="badge bg-success">Programada</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 py-1 btnEditarReunion"
                                    data-id="<?= $r['id'] ?>"
                                    data-quincena="<?= $r['numero_quincena'] ?>"
                                    data-fecha="<?= $r['fecha_reunion'] ?>"
                                    data-hora="<?= $r['hora_reunion'] ?>"
                                    data-cuota="<?= $r['valor_cuota_base'] ?>"
                                    data-evento="<?= $r['tipo_evento_extra'] ?>"
                                    data-premio="<?= $r['monto_premio_extra'] ?>"
                                    data-ganador="<?= $r['ganador_socio_id'] ?>"
                                    data-estado="<?= $r['estado'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalEditarReunion">
                                <i class="fa-solid fa-pen-to-square me-1"></i>Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Editar Reunión -->
<div class="modal fade" id="modalEditarReunion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-calendar-pen me-2"></i>Editar Fecha y Cuota de la Reunión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/reuniones/actualizar" method="POST">
                <input type="hidden" name="reunion_id" id="edit_reunion_id">
                <div class="modal-body p-4">
                    <p class="fw-bold font-outfit text-primary fs-6 mb-3">Modificando Reunión Nº <span id="lblQuincenaNum"></span></p>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="edit_fecha_reunion" class="form-label fw-semibold fs-7">Fecha de Reunión</label>
                            <input type="date" name="fecha_reunion" id="edit_fecha_reunion" class="form-control fw-bold" required>
                        </div>
                        <div class="col-5">
                            <label for="edit_hora_reunion" class="form-label fw-semibold fs-7">Hora</label>
                            <input type="time" name="hora_reunion" id="edit_hora_reunion" class="form-control fw-bold" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_valor_cuota_base" class="form-label fw-semibold fs-7">Valor Cuota Base (COP)</label>
                            <input type="text" name="valor_cuota_base" id="edit_valor_cuota_base" class="form-control money-input text-success fw-bold" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_tipo_evento_extra" class="form-label fw-semibold fs-7">Tipo de Evento</label>
                            <select name="tipo_evento_extra" id="edit_tipo_evento_extra" class="form-select" onchange="autoFillPremio(this.value)">
                                <option value="NINGUNO">Ninguno (Regular $55k)</option>
                                <option value="RIFA">Rifa Interna ($150k)</option>
                                <option value="RONDA">Ronda de Turno ($300k)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_monto_premio_extra" class="form-label fw-semibold fs-7">Monto Premio (COP)</label>
                            <input type="text" name="monto_premio_extra" id="edit_monto_premio_extra" class="form-control money-input text-warning fw-bold">
                        </div>
                        <div class="col-6">
                            <label for="edit_estado" class="form-label fw-semibold fs-7">Estado Reunión</label>
                            <select name="estado" id="edit_estado" class="form-select">
                                <option value="PROGRAMADA">Programada</option>
                                <option value="EN_PROCESO">En Proceso</option>
                                <option value="CERRADA">Cerrada</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_ganador_socio_id" class="form-label fw-semibold fs-7">Socio Ganador de Sorteo / Turno</label>
                        <select name="ganador_socio_id" id="edit_ganador_socio_id" class="form-select">
                            <option value="">-- Sin Ganador Registrado --</option>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Nueva Reunión -->
<div class="modal fade" id="modalNuevaReunion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-calendar-plus me-2"></i>Programar Nueva Reunión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/reuniones/crear" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="new_fecha_reunion" class="form-label fw-semibold fs-7">Fecha de Reunión</label>
                            <input type="date" name="fecha_reunion" id="new_fecha_reunion" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-5">
                            <label for="new_hora_reunion" class="form-label fw-semibold fs-7">Hora</label>
                            <input type="time" name="hora_reunion" id="new_hora_reunion" class="form-control fw-bold" value="14:00" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="new_valor_cuota_base" class="form-label fw-semibold fs-7">Valor Cuota Base (COP)</label>
                            <input type="text" name="valor_cuota_base" id="new_valor_cuota_base" class="form-control money-input text-success fw-bold" value="55.000" required>
                        </div>
                        <div class="col-6">
                            <label for="new_tipo_evento_extra" class="form-label fw-semibold fs-7">Tipo de Evento</label>
                            <select name="tipo_evento_extra" id="new_tipo_evento_extra" class="form-select" onchange="autoFillPremioNew(this.value)">
                                <option value="NINGUNO">Ninguno (Regular $55k)</option>
                                <option value="RIFA">Rifa Interna ($150k)</option>
                                <option value="RONDA">Ronda de Turno ($300k)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_monto_premio_extra" class="form-label fw-semibold fs-7">Monto Premio (COP)</label>
                        <input type="text" name="monto_premio_extra" id="new_monto_premio_extra" class="form-control money-input text-warning fw-bold" value="0">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">Crear Reunión</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function autoFillPremioNew(evento) {
    const inputPremio = document.getElementById('new_monto_premio_extra');
    const inputCuota = document.getElementById('new_valor_cuota_base');
    if (evento === 'RIFA') {
        inputPremio.value = 150000;
        inputCuota.value = 60000;
    } else if (evento === 'RONDA') {
        inputPremio.value = 300000;
        inputCuota.value = 65000;
    } else {
        inputPremio.value = 0;
        inputCuota.value = 55000;
    }
}
function autoFillPremio(evento) {
    const inputPremio = document.getElementById('edit_monto_premio_extra');
    const inputCuota = document.getElementById('edit_valor_cuota_base');
    if (evento === 'RIFA') {
        inputPremio.value = 150000;
        inputCuota.value = 60000;
    } else if (evento === 'RONDA') {
        inputPremio.value = 300000;
        inputCuota.value = 65000;
    } else {
        inputPremio.value = 0;
        inputCuota.value = 55000;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const btnsEditar = document.querySelectorAll('.btnEditarReunion');
    btnsEditar.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_reunion_id').value = btn.getAttribute('data-id');
            document.getElementById('lblQuincenaNum').innerText = btn.getAttribute('data-quincena');
            document.getElementById('edit_fecha_reunion').value = btn.getAttribute('data-fecha');
            document.getElementById('edit_hora_reunion').value = btn.getAttribute('data-hora');
            document.getElementById('edit_valor_cuota_base').value = btn.getAttribute('data-cuota');
            document.getElementById('edit_tipo_evento_extra').value = btn.getAttribute('data-evento');
            document.getElementById('edit_monto_premio_extra').value = btn.getAttribute('data-premio');
            document.getElementById('edit_ganador_socio_id').value = btn.getAttribute('data-ganador') || '';
            document.getElementById('edit_estado').value = btn.getAttribute('data-estado');
        });
    });
});
</script>
