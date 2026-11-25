<link rel="stylesheet" href="/css/pages/gestion-dinero.css">
<link rel="stylesheet" href="/css/pages/fondos.css">

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
                            <?php $ap = FondoBeneficio::aportesDeReunion($r); ?>
                            <?php if ($ap['ronda'] > $cfgFondos['ronda_base']): ?>
                                <span class="gd-tag gd-tag--ronda">Ronda extra +$<?= number_format($ap['ronda'] - $cfgFondos['ronda_base'], 0, ',', '.') ?></span>
                            <?php elseif ($ap['rifa'] > $cfgFondos['rifa_base']): ?>
                                <span class="gd-tag gd-tag--rifa">Rifa extra +$<?= number_format($ap['rifa'] - $cfgFondos['rifa_base'], 0, ',', '.') ?></span>
                            <?php else: ?>
                                <span class="gd-tag gd-tag--reg">Regular ($<?= number_format($cfgFondos['cuota_base'] / 1000, 0, ',', '.') ?>k)</span>
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
                                        data-aporte-ronda="<?= $ap['ronda'] ?>"
                                        data-aporte-rifa="<?= $ap['rifa'] ?>"
                                        data-premios-ronda="<?= htmlspecialchars((string)($r['premios_ronda_planificados'] ?? '')) ?>"
                                        data-premios-rifa="<?= htmlspecialchars((string)($r['premios_rifa_planificados'] ?? '')) ?>"
                                        data-plan-ronda="<?= (int)($planReal[(int)$r['id']]['RONDA']['personas_liberadas_planificadas'] ?? 0) ?>"
                                        data-plan-rifa="<?= (int)($planReal[(int)$r['id']]['RIFA']['personas_liberadas_planificadas'] ?? 0) ?>"
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

<!-- Fondos de ronda y rifa: plan vs real y simulador de distribución -->
<?php
$fm = function ($v) { return '$' . number_format((float)$v, 0, ',', '.'); };
$reunionesOrden = $reuniones;
usort($reunionesOrden, function ($a, $b) { return (int)$a['numero_quincena'] <=> (int)$b['numero_quincena']; });
?>
<details class="fd-panel mt-3">
    <summary><i class="fa-solid fa-scale-balanced me-1"></i>Fondos de ronda y rifa: plan vs real (<?= (int)$cfgFondos['cupos'] ?> cupos)</summary>
    <div class="table-responsive">
        <table class="table table-sm fd-table align-middle mb-2">
            <thead>
                <tr>
                    <th rowspan="2">Reunión</th>
                    <th colspan="4" class="text-center">Ronda (<?= $fm($cfgFondos['premio_ronda']) ?>)</th>
                    <th colspan="4" class="text-center">Rifa (<?= $fm($cfgFondos['premio_rifa']) ?>)</th>
                </tr>
                <tr>
                    <th>Aporte</th><th>Cobrado</th><th>Premios plan/real</th><th class="text-end">Reservado plan/real</th>
                    <th>Aporte</th><th>Cobrado</th><th>Premios plan/real</th><th class="text-end">Reservado plan/real</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reunionesOrden as $r): $pr = $planReal[(int)$r['id']] ?? []; ?>
                    <tr>
                        <td>R<?= (int)$r['numero_quincena'] ?><?= $r['estado'] === 'CERRADA' ? ' <i class="fa-solid fa-lock fd-muted"></i>' : '' ?></td>
                        <?php foreach (['RONDA', 'RIFA'] as $t): $c = $pr[$t] ?? null; ?>
                            <?php if ($c): ?>
                                <td><?= $fm($c['aporte_por_socio']) ?></td>
                                <td class="<?= (float)$c['real_registros'] > 0 && abs($c['real_cobrado'] - $c['total_recaudado']) > 0.5 ? 'fd-diff' : '' ?>"><?= (int)$c['real_registros'] > 0 ? $fm($c['real_cobrado']) : '—' ?></td>
                                <td><?= (int)$c['personas_liberadas_planificadas'] ?> / <?= (int)$c['entregas_reales_count'] ?></td>
                                <td class="text-end"><?= $fm($c['saldo_acumulado_fondo']) ?> / <?= $fm($c['saldo_real_acumulado']) ?></td>
                            <?php else: ?><td colspan="4">—</td><?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="fd-sim">
        <span class="fd-label">Sugerir distribución de cuotas extra</span>
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label for="sim_cupos" class="form-label mb-0">Cupos</label>
                <input type="number" min="1" id="sim_cupos" class="form-control form-control-sm" value="<?= (int)$cfgFondos['cupos'] ?>" style="width:90px">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSimularFondos">Simular</button>
            <form action="/admin/reuniones/aplicar-distribucion" method="POST" id="formAplicarDistribucion" class="d-none"
                  onsubmit="return confirm('Se reescribirán cuota y fondos de las reuniones NO cerradas. ¿Continuar?');">
                <input type="hidden" name="cupos" id="aplicar_cupos" value="<?= (int)$cfgFondos['cupos'] ?>">
                <button type="submit" class="btn btn-sm btn-primary">Aplicar a reuniones no cerradas</button>
            </form>
        </div>
        <div id="resultadoSimulacion" class="mt-2"></div>
    </div>
</details>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btnSimularFondos');
    if (!btn) return;
    const fmt = v => '$' + Math.round(v).toLocaleString('es-CO');
    btn.addEventListener('click', () => {
        const cupos = document.getElementById('sim_cupos').value;
        const out = document.getElementById('resultadoSimulacion');
        out.textContent = 'Simulando...';
        fetch('/admin/reuniones/simular-fondos?cupos=' + encodeURIComponent(cupos))
            .then(r => r.json())
            .then(d => {
                const s = d.simulacion;
                document.getElementById('aplicar_cupos').value = cupos;
                let h = '<div class="' + (s.ok ? 'fd-ok' : 'fd-warn') + '">' + (s.ok ? 'Cierra en $0 con todos servidos' : 'No cierra: ' + s.mensajes.join(' '))
                      + ' · extras ronda ' + s.extras_ronda + ', rifa ' + s.extras_rifa
                      + (s.separacion_minima ? ' · separación mínima ' + s.separacion_minima + ' reuniones' : '') + '</div>';
                h += '<div class="table-responsive"><table class="table table-sm fd-table"><thead><tr><th>R</th><th>Cuota</th><th>Extra</th><th>Ronda pers.</th><th>Rifa pers.</th><th>Saldo ronda</th><th>Saldo rifa</th></tr></thead><tbody>';
                s.filas.forEach(f => {
                    h += '<tr><td>R' + f.numero + (f.bloqueada ? ' (cerrada)' : '') + '</td><td>' + fmt(f.cuota) + '</td><td>' + (f.extra === 'NINGUNO' ? '' : f.extra)
                       + '</td><td>' + f.personas_ronda + '</td><td>' + f.personas_rifa + '</td><td>' + fmt(f.saldo_ronda) + '</td><td>' + fmt(f.saldo_rifa) + '</td></tr>';
                });
                out.innerHTML = h + '</tbody></table></div>';
                document.getElementById('formAplicarDistribucion').classList.toggle('d-none', !s.ok);
            })
            .catch(() => { out.textContent = 'No se pudo simular.'; });
    });
});
</script>


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

                    <div class="fd-edit mb-3" id="bloqueFondosEdicion">
                        <span class="fd-label">Fondos de esta reunión (por socio) y premios planificados</span>
                        <div class="row g-2">
                            <div class="col-6 col-sm-3">
                                <label for="edit_aporte_ronda" class="form-label">Aporte ronda</label>
                                <input type="text" name="aporte_ronda" id="edit_aporte_ronda" class="form-control money-input" inputmode="numeric">
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="edit_aporte_rifa" class="form-label">Aporte rifa</label>
                                <input type="text" name="aporte_rifa" id="edit_aporte_rifa" class="form-control money-input" inputmode="numeric">
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="edit_premios_ronda" class="form-label">Premios ronda</label>
                                <input type="number" min="0" max="<?= (int)$cfgFondos['cupos'] ?>" name="premios_ronda_planificados" id="edit_premios_ronda" class="form-control" placeholder="auto">
                            </div>
                            <div class="col-6 col-sm-3">
                                <label for="edit_premios_rifa" class="form-label">Premios rifa</label>
                                <input type="number" min="0" max="<?= (int)$cfgFondos['cupos'] ?>" name="premios_rifa_planificados" id="edit_premios_rifa" class="form-control" placeholder="auto">
                            </div>
                        </div>
                        <small class="fd-muted">Si cambias los aportes, la cuota pasa a ser ahorro + ronda + rifa. Vacío = el máximo que alcanza. En reuniones cerradas no se modifican.</small>
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
const NT_FONDOS = <?= json_encode($cfgFondos) ?>;
function autoFillPremioNew(evento) {
    const inputPremio = document.getElementById('new_monto_premio_extra');
    const inputCuota = document.getElementById('new_valor_cuota_base');
    if (evento === 'RIFA') {
        inputPremio.value = NT_FONDOS.premio_rifa;
        inputCuota.value = NT_FONDOS.cuota_base + NT_FONDOS.extra_rifa;
    } else if (evento === 'RONDA') {
        inputPremio.value = NT_FONDOS.premio_ronda;
        inputCuota.value = NT_FONDOS.cuota_base + NT_FONDOS.extra_ronda;
    } else {
        inputPremio.value = 0;
        inputCuota.value = NT_FONDOS.cuota_base;
    }
}
function autoFillPremio(evento) {
    const inputPremio = document.getElementById('edit_monto_premio_extra');
    const inputCuota = document.getElementById('edit_valor_cuota_base');
    if (evento === 'RIFA') {
        inputPremio.value = NT_FONDOS.premio_rifa;
        inputCuota.value = NT_FONDOS.cuota_base + NT_FONDOS.extra_rifa;
    } else if (evento === 'RONDA') {
        inputPremio.value = NT_FONDOS.premio_ronda;
        inputCuota.value = NT_FONDOS.cuota_base + NT_FONDOS.extra_ronda;
    } else {
        inputPremio.value = 0;
        inputCuota.value = NT_FONDOS.cuota_base;
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
            document.getElementById('edit_aporte_ronda').value = btn.getAttribute('data-aporte-ronda');
            document.getElementById('edit_aporte_rifa').value = btn.getAttribute('data-aporte-rifa');
            document.getElementById('edit_premios_ronda').value = btn.getAttribute('data-premios-ronda');
            document.getElementById('edit_premios_rifa').value = btn.getAttribute('data-premios-rifa');
            document.getElementById('edit_premios_ronda').placeholder = 'auto (' + btn.getAttribute('data-plan-ronda') + ')';
            document.getElementById('edit_premios_rifa').placeholder = 'auto (' + btn.getAttribute('data-plan-rifa') + ')';
            const cerrada = btn.getAttribute('data-estado') === 'CERRADA';
            document.querySelectorAll('#bloqueFondosEdicion input').forEach(i => { i.disabled = cerrada; });
        });
    });
});
</script>
