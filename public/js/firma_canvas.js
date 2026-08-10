/**
 * InfoNatillera - Pad de Firma Digital HTML5 Canvas & Captura de Foto Evidencia
 * Soporta pantallas táctiles móviles (Mobile-First) y puntero de escritorio.
 * Ajusta automáticamente escala y resolución al abrir Modales de Bootstrap.
 */

function initCanvasSignature(canvasId, btnClearId, inputHiddenId, formId, modalId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const btnClear = btnClearId ? document.getElementById(btnClearId) : null;
    const inputHidden = inputHiddenId ? document.getElementById(inputHiddenId) : null;
    const form = formId ? document.getElementById(formId) : null;
    const modalEl = modalId ? document.getElementById(modalId) : null;

    let dib = false;
    let tieneF = false;

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        if (rect.width > 0) {
            canvas.width = Math.round(rect.width);
            canvas.height = 150;
        }
        ctx.strokeStyle = '#0f172a';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }

    // Ajustar resolución cuando la ventana cargue y al desplegarse el modal
    resizeCanvas();
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', () => {
            resizeCanvas();
        });
    }

    function getCoords(e) {
        const rect = canvas.getBoundingClientRect();
        let clientX = e.clientX;
        let clientY = e.clientY;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        }

        const scaleX = canvas.width / (rect.width || 1);
        const scaleY = canvas.height / (rect.height || 1);

        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function startDraw(e) {
        dib = true;
        tieneF = true;
        const pos = getCoords(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        if (e.cancelable) e.preventDefault();
    }

    function moveDraw(e) {
        if (!dib) return;
        const pos = getCoords(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        if (e.cancelable) e.preventDefault();
    }

    function stopDraw() {
        dib = false;
    }

    // Eventos de ratón de escritorio
    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', moveDraw);
    canvas.addEventListener('mouseup', stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);

    // Eventos táctiles para dispositivos móviles
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', moveDraw, { passive: false });
    canvas.addEventListener('touchend', stopDraw);

    // Botón Limpiar Firma
    if (btnClear) {
        btnClear.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            tieneF = false;
            if (inputHidden) inputHidden.value = '';
        });
    }

    // Guardar imagen Base64 al enviar el formulario
    if (form) {
        form.addEventListener('submit', () => {
            if (tieneF && inputHidden) {
                inputHidden.value = canvas.toDataURL('image/png');
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicializar Firma Modal Entregas Beneficios
    initCanvasSignature('canvasFirma', 'btnLimpiarFirma', 'firma_base64', 'formRegistrarEntrega', 'modalRegistrarEntrega');

    // 2. Inicializar Firma Modal Nuevo Préstamo
    initCanvasSignature('canvasFirmaPrestamo', 'btnLimpiarFirmaPrestamo', 'firma_base64_prestamo', 'formNuevoPrestamo', 'modalNuevoPrestamo');

    // Lógica dinámica de selección de beneficiarios según tipo en Entregas
    const selectTipo = document.getElementById('entrega_tipo_beneficio');
    const inputMonto = document.getElementById('entrega_monto_entregado');
    const selectSocio = document.getElementById('entrega_socio_id');

    if (selectTipo && inputMonto) {
        selectTipo.addEventListener('change', (e) => {
            const val = e.target.value;
            if (val === 'RONDA') {
                inputMonto.value = typeof formatMoneyString === 'function' ? formatMoneyString('300000') : '300.000';
            } else if (val === 'RIFA') {
                inputMonto.value = typeof formatMoneyString === 'function' ? formatMoneyString('150000') : '150.000';
            } else if (val === 'PRESTAMO') {
                inputMonto.value = typeof formatMoneyString === 'function' ? formatMoneyString('500000') : '500.000';
            }
            cargarSociosPendientes(val);
        });
    }

    function cargarSociosPendientes(tipo) {
        if (!selectSocio) return;
        selectSocio.innerHTML = '<option value="">Cargando socios...</option>';

        fetch(`/admin/entregas/socios-pendientes-json?tipo=${tipo}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.socios) {
                    if (data.socios.length === 0) {
                        selectSocio.innerHTML = `<option value="">-- No hay socios disponibles --</option>`;
                        return;
                    }

                    const labelHeader = (tipo === 'PRESTAMO') ? 'Socio Deudor' : `Socio Beneficiario (${data.socios.length} pendientes)`;
                    let opts = `<option value="">-- Seleccionar ${labelHeader} --</option>`;
                    data.socios.forEach(s => {
                        opts += `<option value="${s.id}">${s.nombre_completo} (C.C. ${s.cedula})</option>`;
                    });
                    selectSocio.innerHTML = opts;
                }
            })
            .catch(err => {
                console.error(err);
                selectSocio.innerHTML = '<option value="">Error al cargar socios</option>';
            });
    }
});
