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
            <h5 class="font-outfit fw-bold text-warning mb-1"><i class="fa-solid fa-tower-broadcast me-2"></i>Estado de Notificaciones Nativas (Web Push VAPID)</h5>
            <p class="text-white-50 fs-7 m-0">
                Permite a tu navegador o dispositivo recibir avisos automáticos. Si tu dispositivo o navegador no lo soporta (como iOS Safari o modo incógnito), 
                <strong>todas las notificaciones quedan guardadas dentro de la plataforma en la Campanita 🔔 y puedes difundirlas a tu Grupo de WhatsApp con 1 clic.</strong>
            </p>
        </div>
        <button type="button" class="btn btn-warning fw-bold rounded-pill px-4 shadow-sm" id="btnActivarNotifications" data-vapid-key="<?= htmlspecialchars($vapidPublicKey) ?>">
            <i class="fa-solid fa-bell me-1"></i>Activar en mi Navegador
        </button>
    </div>
</div>

<!-- Historial de Notificaciones Enviadas -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="font-outfit fw-bold m-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de Mensajes Emitidos</h5>
        <span class="badge bg-secondary font-outfit fs-7">Total: <?= count($historial) ?> avisos</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light font-outfit fs-7 text-uppercase">
                <tr>
                    <th class="ps-4">Título / Mensaje</th>
                    <th>Destinatario</th>
                    <th>Emitido Por</th>
                    <th>Fecha / Hora</th>
                    <th class="text-end pe-4">Difusión WhatsApp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historial)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No se han emitido notificaciones en el historial.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historial as $n): 
                        $textWa = "📢 *INFONATILLERA - NOTIFICACIÓN*\n\n📌 *{$n['titulo']}*\n{$n['mensaje']}\n\n🌐 *Ingresa a la plataforma aquí:*\nhttps://natillera.skylinedev.top/";
                        $urlWa = "https://api.whatsapp.com/send?text=" . urlencode($textWa);
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark font-outfit"><?= htmlspecialchars($n['titulo']) ?></div>
                                <small class="text-muted d-block text-wrap" style="max-width: 350px;"><?= htmlspecialchars($n['mensaje']) ?></small>
                            </td>
                            <td>
                                <?php if ($n['destinatario_tipo'] === 'TODOS'): ?>
                                    <span class="badge bg-danger rounded-pill"><i class="fa-solid fa-bullhorn me-1"></i>Todos los Socios</span>
                                <?php else: ?>
                                    <span class="badge bg-primary rounded-pill"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($n['destinatario_nombre'] ?: 'Socio Directo') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="fs-7 text-muted"><?= htmlspecialchars($n['remitente_nombre']) ?></td>
                            <td class="fs-7 text-muted"><?= date('d/m/Y h:i A', strtotime($n['fecha_envio'])) ?></td>
                            <td class="text-end pe-4">
                                <a href="<?= $urlWa ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill fw-bold shadow-sm px-3">
                                    <i class="fa-brands fa-whatsapp me-1"></i>Compartir por WhatsApp
                                </a>
                            </td>
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
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-paper-plane me-2"></i>Emitir Notificación a Socios</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/notificaciones/enviar" method="POST" id="formEnviarNotificacion">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="destinatario_tipo" class="form-label fw-semibold fs-7">Enviar a:</label>
                        <select name="destinatario_tipo" id="destinatario_tipo" class="form-select fw-bold" onchange="toggleSocioSelect(this.value)">
                            <option value="TODOS">Todos los Socios (Mensaje General / Difusión)</option>
                            <option value="SOCIO_ESPECIFICO">Socio en Específico</option>
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
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-success rounded-pill fw-bold" id="btnCompartirWhatsAppDirecto">
                        <i class="fa-brands fa-whatsapp me-1"></i>Compartir por WhatsApp
                    </button>
                    <div>
                        <button type="button" class="btn btn-light rounded-pill me-1" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger rounded-pill fw-bold px-4">Emitir Notificación</button>
                    </div>
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

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnCompartirWhatsAppDirecto')?.addEventListener('click', () => {
        const t = document.getElementById('titulo').value.trim();
        const m = document.getElementById('mensaje').value.trim();
        if (!t || !m) {
            Swal.fire('Atención', 'Ingresa título y mensaje para poder generar el mensaje de WhatsApp.', 'warning');
            return;
        }
        const textWa = `📢 *INFONATILLERA - NOTIFICACIÓN*\n\n📌 *${t}*\n${m}\n\n🌐 *Ingresa a la plataforma aquí:*\nhttps://natillera.skylinedev.top/`;
        window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(textWa)}`, '_blank');
    });
});
</script>
