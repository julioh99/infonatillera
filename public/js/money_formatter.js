/**
 * InfoNatillera - Formateador Universal de Campos Monetarios
 * Aplica punto a cada tres dígitos en tiempo real a todos los inputs con clase .money-input
 */

function formatMoneyString(val) {
    if (val === null || val === undefined || val === '') return '';
    const clean = val.toString().replace(/\D/g, '');
    if (!clean) return '';
    return new Intl.NumberFormat('es-CO').format(parseInt(clean, 10));
}

function unformatMoneyString(val) {
    if (!val) return '0';
    return val.toString().replace(/\./g, '').replace(/,/g, '').trim();
}

function initMoneyInputs(container = document) {
    const inputs = container.querySelectorAll('.money-input'); 
    inputs.forEach(inp => {
        if (inp.type === 'number') {
            inp.type = 'text';
            inp.setAttribute('inputmode', 'numeric');
        }

        if (inp.value) {
            inp.value = formatMoneyString(inp.value);
        }

        inp.addEventListener('input', (e) => {
            const cursorStart = e.target.selectionStart;
            const oldLen = e.target.value.length;
            const formatted = formatMoneyString(e.target.value);
            e.target.value = formatted;
            const newLen = formatted.length;
            const newPos = cursorStart + (newLen - oldLen);
            if (newPos >= 0 && newPos <= newLen) {
                e.target.setSelectionRange(newPos, newPos);
            }
        });
    });
}

// Desformatear automáticamente antes de enviar el formulario para que PHP reciba enteros/floats limpios
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form && form.querySelectorAll) {
        form.querySelectorAll('.money-input').forEach(inp => {
            inp.value = unformatMoneyString(inp.value);
        });
    }
}, true);

document.addEventListener('DOMContentLoaded', () => {
    initMoneyInputs();
});
