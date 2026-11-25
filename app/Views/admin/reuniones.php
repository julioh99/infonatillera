<link rel="stylesheet" href="/css/pages/gestion-dinero.css">

<div class="gd-head">
    <div class="gd-head__main">
        <span class="gd-head__icon gd-head__icon--gold" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></span>
        <div class="gd-head__text">
            <h2 class="nt-page-title">Programación y Valor de Reuniones</h2>
            <p class="nt-page-sub">Edición de fechas, horas, valor de cuota base ($55k, $60k, $65k) y pasarelas de Rifa/Ronda (exclusivo Presidente y Secretaria General).</p>
        </div>
    </div>
    <div class="gd-head__actions">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaReunion">
            <i class="fa-solid fa-plus me-2"></i>Nueva Reunión
        </button>
    </div>
</div>

<div class="gd-surface">
    <div class="table-responsive">
        <table class="table table-hover align-middle gd-table gd-cards">
            <thead>
                <tr>
                    <th>Reunión</th>
                    <th>Fecha / Hora</th>
                    <th class="text-end">Valor Cuota Base</th>
                    <th>Evento / Pasarela</th>
                    <th class="text-end">Premio Entregado</th>
                    <th>Ganador Sorteo</th>
                    <th>Estado</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reuniones as $r): ?>
                    <tr>
                        <td class="gd-cell-title">
                            <strong class="gd-name">Reunión Nº <?= $r['numero_quincena'] ?></strong>
                        </td>
                        <td data-label="Fecha / Hora">
                            <div>
                                <div class="gd-date"><i class="fa-solid fa-calendar-day me-1"></i><?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></div>
                                <small class="gd-sub"><i class="fa-solid fa-clock me-1"></i><?= $r['hora_reunion'] ?></small>
                            </div>
                        </td>
                        <td class="gd-num gd-fee" data-label="Cuota base">$<?= number_format($r['valor_cuota_base'], 0, ',', '.') ?> COP</td>
                        <td data-label="Evento">
                            <?php if ($r['tipo_evento_extra'] === 'RIFA'): ?>
                                <span class="gd-tag gd-tag--rifa">Rifa ($150k)</span>
                            <?php elseif ($r['tipo_evento_extra'] === 'RONDA'): ?>
                                <span class="gd-tag gd-tag--ronda">Ronda ($300k)</span>
                            <?php else: ?>
                                <span class="gd-tag gd-tag--reg">Regular ($55k)</span>
                            <?php endif; ?>
                        </td>
                        <td class="gd-num" data-label="Premio">
                            <?php if ($r['monto_premio_extra'] > 0): ?>
                                <span class="fw-bold gd-gold font-outfit">$<?= number_format($r['monto_premio_extra'], 0, ',', '.') ?></span>
                            <?php else: ?>
                                <span class="text-muted fs-7">$0</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Ganador">
                            <?php if (!empty($r['ganador_nombre'])): ?>
                                <span class="gd-tag gd-tag--win text-start"><i class="fa-solid fa-trophy"></i><?= htmlspecialchars($r['ganador_nombre']) ?></span>
                            <?php else: ?>
                                <span class="text-muted fs-7">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Estado">
                            <?php if ($r['estado'] === 'CERRADA'): ?>
                                <span class="gd-tag gd-tag--done">Cerrada</span>
                            <?php elseif ($r['estado'] === 'EN_PROCESO'): ?>
                                <span class="gd-tag gd-tag--proc">En Proceso</span>
                            <?php else: ?>
                                <span class="gd-tag gd-tag--prog">Programada</span>
                            <?php endif; ?>
                        </td>
                        <td class="gd-cell-actions text-end">
                            <div class="gd-actions">
                                <button type="button" class="btn btn-outline-secondary rounded-pill gd-act btnEditarReunion"
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
                                    <i class="fa-solid fa-pen-to-square"></i>Editar
                                </button>
                            </div>
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
        <div class="modal-content gd-modal">
            <div class="modal-header gd-mh gd-tone-warning">
                <h5 class="modal-title"><i class="fa-solid fa-calendar-pen"></i>Editar Fecha y Cuota de la Reunión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/admin/reuniones/actualizar" method="POST">
                <input type="hidden" name="reunion_id" id="edit_reunion_id">
                <div class="modal-body">
                    <p class="gd-person__name mb-3">Modificando Reunión Nº <span id="lblQuincenaNum"></span></p>

                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label for="edit_fecha_reunion" class="form-label">Fecha de Reunión</label>
                            <input type="date" name="fecha_reunion" id="edit_fecha_reunion" class="form-control fw-bold" required>
                        </div>
                        <div class="col-5">
                            <label for="edit_hora_reunion" class="form-label">Hora</label>
                            <input type="time" name="hora_reunion" id="edit_hora_reunion" class="form-control fw-bold" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="edit_valor_cuota_base" class="form-label">Valor Cuota Base (COP)</label>
                            <input type="text" name="valor_cuota_base" id="edit_valor_cuota_base" class="form-control money-input gd-pos fw-bold" inputmode="numeric" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="edit_tipo_evento_extra" class="form-label">Tipo de Evento</label>
                            <select name="tipo_evento_extra" id="edit_tipo_evento_extra" class="form-select" onchange="autoFillPremio(this.value)">
                                <option value="NINGUNO">Ninguno (Regular $55k)</option>
                                <option value="RIFA">Rifa Interna ($150k)</option>
                                <option value="RONDA">Ronda de Turno ($300k)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="edit_monto_premio_extra" class="form-label">Monto Premio (COP)</label>
                            <input type="text" name="monto_premio_extra" id="edit_monto_premio_extra" class="form-control money-input gd-gold fw-bold" inputmode="numeric">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="edit_estado" class="form-label">Estado Reunión</label>
                            <select name="estado" id="edit_estado" class="form-select">
                                <option value="PROGRAMADA">Programada</option>
                                <option value="EN_PROCESO">En Proceso</option>
                                <option value="CERRADA">Cerrada</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="edit_ganador_socio_id" class="form-label">Socio Ganador de Sorteo / Turno</label>
                        <select name="ganador_socio_id" id="edit_ganador_socio_id" class="form-select">
                            <option value="">-- Sin Ganador Registrado --</option>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Nueva Reunión -->
<div class="modal fade" id="modalNuevaReunion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gd-modal">
            <div class="modal-header gd-mh gd-tone-warning">
                <h5 class="modal-title"><i class="fa-solid fa-calendar-plus"></i>Programar Nueva Reunión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/admin/reuniones/crear" method="POST">
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label for="new_fecha_reunion" class="form-label">Fecha de Reunión</label>
                            <input type="date" name="fecha_reunion" id="new_fecha_reunion" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-5">
                            <label for="new_hora_reunion" class="form-label">Hora</label>
                            <input type="time" name="hora_reunion" id="new_hora_reunion" class="form-control fw-bold" value="14:00" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="new_valor_cuota_base" class="form-label">Valor Cuota Base (COP)</label>
                            <input type="text" name="valor_cuota_base" id="new_valor_cuota_base" class="form-control money-input gd-pos fw-bold" inputmode="numeric" value="55.000" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="new_tipo_evento_extra" class="form-label">Tipo de Evento</label>
                            <select name="tipo_evento_extra" id="new_tipo_evento_extra" class="form-select" onchange="autoFillPremioNew(this.value)">
                                <option value="NINGUNO">Ninguno (Regular $55k)</option>
                                <option value="RIFA">Rifa Interna ($150k)</option>
                                <option value="RONDA">Ronda de Turno ($300k)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="new_monto_premio_extra" class="form-label">Monto Premio (COP)</label>
                        <input type="text" name="monto_premio_extra" id="new_monto_premio_extra" class="form-control money-input gd-gold fw-bold" inputmode="numeric" pattern="[0-9]*" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Crear Reunión</button>
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
