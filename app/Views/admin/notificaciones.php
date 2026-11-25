<link rel="stylesheet" href="/css/pages/gestion-general.css">

<header class="gg-head">
    <div class="gg-head__text">
        <span class="nt-eyebrow">Comunicación</span>
        <h2 class="nt-page-title gg-head__title">
            <i class="fa-solid fa-bell gg-head__icon gg-head__icon--gold"></i>
            <span>Notificaciones Web Push (VAPID)</span>
        </h2>
        <p class="nt-page-sub m-0">Envío de notificaciones nativas al navegador a todos los socios o individuo.</p>
    </div>
    <div class="gg-head__actions">
        <button type="button" class="btn btn-primary px-3" data-bs-toggle="modal" data-bs-target="#modalEnviarPush">
            <i class="fa-solid fa-paper-plane me-1"></i>Redactar Notificación
        </button>
    </div>
</header>

<!-- Banner de Activación de Suscripción en el Navegador -->
<div class="gg-banner">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h5 class="gg-banner__title"><i class="fa-solid fa-tower-broadcast me-2"></i>Estado de Notificaciones Nativas (Web Push VAPID)</h5>
            <p class="gg-banner__text">
                Permite a tu navegador o dispositivo recibir avisos automáticos. Si tu dispositivo o navegador no lo soporta (como iOS Safari o modo incógnito), 
                <strong>todas las notificaciones quedan guardadas dentro de la plataforma en la Campanita 🔔 y puedes difundirlas a tu Grupo de WhatsApp con 1 clic.</strong>
            </p>
            <?php if (empty($pushConfigurado)): ?>
                <p class="gg-banner__text mb-0"><strong>Atención:</strong> el servidor no tiene configuradas las llaves VAPID, por eso los avisos no llegan al navegador (siguen en la Campanita).</p>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-column align-items-end gap-2">
            <span id="pushEstado" class="badge bg-secondary">Revisando...</span>
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                <button type="button" class="btn btn-warning px-4" id="btnActivarNotifications" data-vapid-key="<?= htmlspecialchars($vapidPublicKey) ?>">
                    <i class="fa-solid fa-bell me-1"></i>Activar en mi Navegador
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnProbarPush">
                    <i class="fa-solid fa-vial me-1"></i>Enviarme una prueba
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Historial de Notificaciones Enviadas -->
<div class="gg-panel">
    <div class="gg-panel__head">
        <h5 class="gg-panel__title"><i class="fa-solid fa-clock-rotate-left"></i>Historial de Mensajes Emitidos</h5>
        <span class="nt-chip">Total: <?= count($historial) ?> avisos</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle gg-table gg-stack">
            <thead>
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
                        <td colspan="5" class="gg-empty">No se han emitido notificaciones en el historial.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historial as $n): 
                        $textWa = "📢 *INFONATILLERA - NOTIFICACIÓN*\n\n📌 *{$n['titulo']}*\n{$n['mensaje']}\n\n🌐 *Ingresa a la plataforma aquí:*\nhttps://natillera.skylinedev.top/";
                        $urlWa = "https://api.whatsapp.com/send?text=" . urlencode($textWa);
                    ?>
                        <tr>
                            <td class="ps-4 gg-cell-main">
                                <div class="min-w-0">
                                <div class="gg-name"><?= htmlspecialchars($n['titulo']) ?></div>
                                <small class="text-muted d-block text-wrap gg-wrap" style="max-width: 350px;"><?= htmlspecialchars($n['mensaje']) ?></small>
                                </div>
                            </td>
                            <td data-label="Destinatario">
                                <?php if ($n['destinatario_tipo'] === 'TODOS'): ?>
                                    <span class="badge bg-danger rounded-pill"><i class="fa-solid fa-bullhorn me-1"></i>Todos los Socios</span>
                                <?php else: ?>
                                    <span class="badge bg-primary rounded-pill"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($n['destinatario_nombre'] ?: 'Socio Directo') ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Emitido por" class="fs-7 text-muted gg-wrap"><?= htmlspecialchars($n['remitente_nombre']) ?></td>
                            <td data-label="Fecha / hora" class="fs-7 text-muted"><?= date('d/m/Y h:i A', strtotime($n['fecha_envio'])) ?></td>
                            <td class="text-end pe-4 gg-cell-actions">
                                <a href="<?= $urlWa ?>" target="_blank" class="btn btn-sm btn-outline-success px-3 d-inline-flex align-items-center justify-content-center">
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content gg-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-paper-plane me-2"></i>Emitir Notificación a Socios</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/notificaciones/enviar" method="POST" id="formEnviarNotificacion">
                <div class="modal-body">
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
                <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap">
                    <button type="button" class="btn btn-outline-success" id="btnCompartirWhatsAppDirecto">
                        <i class="fa-brands fa-whatsapp me-1"></i>Compartir por WhatsApp
                    </button>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4">Emitir Notificación</button>
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
