/**
 * InfoNatillera - Pad de Firma Digital HTML5 Canvas & Captura de Foto Evidencia
 * Soporta pantallas táctiles móviles (Mobile-First) y puntero de escritorio.
 */

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('canvasFirma');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const btnLimpiar = document.getElementById('btnLimpiarFirma');
    const inputFirmaBase64 = document.getElementById('firma_base64');
    const selectTipo = document.getElementById('entrega_tipo_beneficio');
    const inputMonto = document.getElementById('entrega_monto_entregado');
    const selectSocio = document.getElementById('entrega_socio_id');
    const formEntrega = document.getElementById('formRegistrarEntrega');

    let dibujando = false;
    let tieneFirma = false;

    // Configuración del trazado
    ctx.strokeStyle = '#0f172a';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    function obtenerCoordenadas(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function iniciarTrazo(e) {
        dibujando = true;
        tieneFirma = true;
        const pos = obtenerCoordenadas(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        e.preventDefault();
    }

    function dibujarTrazo(e) {
        if (!dibujando) return;
        const pos = obtenerCoordenadas(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        e.preventDefault();
    }

    function detenerTrazo() {
        dibujando = false;
    }

    // Eventos de ratón
    canvas.addEventListener('mousedown', iniciarTrazo);
    canvas.addEventListener('mousemove', dibujarTrazo);
    canvas.addEventListener('mouseup', detenerTrazo);
    canvas.addEventListener('mouseleave', detenerTrazo);

    // Eventos táctiles para dispositivos móviles
    canvas.addEventListener('touchstart', iniciarTrazo, { passive: false });
    canvas.addEventListener('touchmove', dibujarTrazo, { passive: false });
    canvas.addEventListener('touchend', detenerTrazo);

    // Limpiar Firma
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            tieneFirma = false;
            if (inputFirmaBase64) inputFirmaBase64.value = '';
        });
    }

    // Sincronizar monto según beneficio
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

    // Preparar firma al enviar formulario
    if (formEntrega) {
        formEntrega.addEventListener('submit', (e) => {
            if (tieneFirma && inputFirmaBase64) {
                inputFirmaBase64.value = canvas.toDataURL('image/png');
            }
        });
    }
});
