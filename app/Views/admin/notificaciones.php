<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-bell text-danger"></i>
            Notificaciones Web Push (VAPID)
        </h2>
        <p class="text-muted m-0 fs-7">Envío de notificaciones nativas al navegador a todos los socios o individuo.</p>
    </div>
    <div class="col-12 col-md-6 text-md-end">
        <button type="button" class="btn btn-danger rounded-pill fw-bold btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalEnviarPush">
            <i class="fa-solid fa-paper-plane me-1"></i>Redactar Notificación
        </button>
    </div>
</div>

<!-- Banner de Activación de Suscripción en el Navegador -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient-dark text-white p-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h5 class="font-outfit fw-bold text-warning mb-1"><i class="fa-solid fa-tower-broadcast me-2"></i>Estado de Notificaciones Nativas</h5>
            <p class="text-white-50 fs-7 m-0">Permite a este dispositivo recibir avisos de reunión (8:00 AM, 12:00 PM y 1:00 PM) y mensajes directos.</p>
        </div>
        <button type="button" class="btn btn-warning fw-bold rounded-pill px-4" id="btnActivarNotifications" data-vapid-key="<?= htmlspecialchars($vapidPublicKey) ?>">
            <i class="fa-solid fa-bell me-1"></i>Activar en mi Navegador
        </button>
    </div>
</div>

<!-- Historial de Notificaciones Enviadas -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-dark text-white p-3">
        <h5 class="font-outfit fw-bold m-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de Mensajes Emitidos</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light font-outfit fs-7 text-uppercase">
                <tr>
                    <th class="ps-4">Título / Mensaje</th>
                    <th>Destinatario</th>
                    <th>Emitido Por</th>
                    <th class="text-end pe-4">Fecha / Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historial)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No se han emitido notificaciones en el historial.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historial as $n): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark font-outfit"><?= htmlspecialchars($n['titulo']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($n['mensaje']) ?></small>
                            </td>
                            <td>
                                <?php if ($n['destinatario_tipo'] === 'TODOS'): ?>
                                    <span class="badge bg-danger rounded-pill">Todos los Socios (Broadcast)</span>
                                <?php else: ?>
                                    <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($n['destinatario_nombre'] ?: 'Socio Directo') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="fs-7 text-muted"><?= htmlspecialchars($n['remitente_nombre']) ?></td>
                            <td class="text-end pe-4 fs-7 text-muted"><?= date('d/m/Y h:i A', strtotime($n['fecha_envio'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Enviar Push -->
<div class="modal fade" id="modalEnviarPush" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-paper-plane me-2"></i>Emitir Notificación Web Push</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/notificaciones/enviar" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="destinatario_tipo" class="form-label fw-semibold fs-7">Enviar a:</label>
                        <select name="destinatario_tipo" id="destinatario_tipo" class="form-select" onchange="toggleSocioSelect(this.value)">
                            <option value="TODOS">Todos los Socios (Mensaje General)</option>
                            <option value="SOCIO_ESPECIFICO">Socio en Especifico</option>
                        </select>
                    </div>

                    <div id="divSocioSelect" class="mb-3 d-none">
                        <label for="socio_id_push" class="form-label fw-semibold fs-7">Seleccionar Socio</label>
                        <select name="socio_id" id="socio_id_push" class="form-select">
                            <option value="">-- Seleccionar Socio --</option>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="titulo" class="form-label fw-semibold fs-7">Título del Mensaje</label>
                        <input type="text" name="titulo" id="titulo" class="form-control fw-bold" placeholder="Ej: Recordatorio de Reunión Hoy" required>
                    </div>

                    <div class="mb-3">
                        <label for="mensaje" class="form-label fw-semibold fs-7">Contenido del Mensaje</label>
                        <textarea name="mensaje" id="mensaje" class="form-control" rows="3" placeholder="Ej: Hoy a las 2:00 PM nos reunimos. Cuota de la reunión: $55.000 COP." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-pill fw-bold px-4">Emitir Notificación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSocioSelect(tipo) {
    const div = document.getElementById('divSocioSelect');
    if (tipo === 'SOCIO_ESPECIFICO') {
        div.classList.remove('d-none');
    } else {
        div.classList.add('d-none');
    }
}
</script>
