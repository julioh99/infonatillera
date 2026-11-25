/**
 * orden_socios.js - lista de socios ordenable (arrastrar, flechas, A-Z y volver al orden actual).
 * Sirve en Gestion de Socios y en Historico de Anos. Funciona con dedo (pointer events) y con teclado.
 *
 * Marcado esperado:
 *   <ul class="nt-orden" data-nt-orden>
 *     <li class="nt-orden__item" data-id="12" data-nombre="juan perez">
 *       <button type="button" class="nt-orden__handle" aria-label="Arrastrar"><i class="fa-solid fa-grip-vertical"></i></button>
 *       <span class="nt-orden__pos"></span> <span class="nt-orden__name">Juan Perez</span>
 *       <span class="nt-orden__btns"><button type="button" class="nt-orden__up">..</button><button type="button" class="nt-orden__down">..</button></span>
 *       <input type="hidden" name="orden[]" value="12">
 *     </li>
 *   </ul>
 *   <button type="button" data-nt-orden-az="#idLista">A-Z</button>
 *   <button type="button" data-nt-orden-reset="#idLista">Volver al orden actual</button>
 * El orden de envio es el orden de los <li> en el DOM.
 */
(function () {
    'use strict';

    var CSS = '' +
        '.nt-orden{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.35rem}' +
        '.nt-orden__item{display:flex;align-items:center;gap:.6rem;padding:.45rem .6rem;background:var(--nt-surface,#fff);border-radius:var(--nt-radius-sm,10px);box-shadow:var(--nt-shadow-1,0 0 0 1px rgba(23,33,28,.06));min-width:0}' +
        '.nt-orden__item.is-dragging{box-shadow:var(--nt-shadow-3,0 8px 24px rgba(0,0,0,.2));background:var(--nt-pine-50,#f0faf4);position:relative;z-index:2}' +
        '.nt-orden__item.is-off{opacity:.5}' +
        '.nt-orden__handle{border:0;background:transparent;color:var(--nt-muted,#5b6a62);padding:.4rem .5rem;cursor:grab;touch-action:none;border-radius:8px;line-height:1;min-width:2.25rem;min-height:2.25rem}' +
        '.nt-orden__handle:active{cursor:grabbing}' +
        '.nt-orden__handle:focus-visible,.nt-orden__btns button:focus-visible{outline:2px solid var(--nt-pine-500,#2a8a60);outline-offset:1px}' +
        '.nt-orden__pos{flex:0 0 2rem;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;color:var(--nt-pine-700,#17543a);font-size:.85rem}' +
        '.nt-orden__name{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600}' +
        '.nt-orden__sub{display:block;font-weight:400;font-size:.75rem;color:var(--nt-muted,#5b6a62)}' +
        '.nt-orden__btns{display:flex;gap:.2rem;flex:0 0 auto}' +
        '.nt-orden__btns button{border:0;background:var(--nt-pine-50,#f0faf4);color:var(--nt-pine-700,#17543a);width:2.25rem;height:2.25rem;border-radius:8px;line-height:1}' +
        '@media (prefers-reduced-motion:no-preference){.nt-orden__item{transition:box-shadow 140ms ease-out,background-color 140ms ease-out}}';

    function inyectarCss() {
        if (document.getElementById('nt-orden-css')) return;
        var s = document.createElement('style');
        s.id = 'nt-orden-css';
        s.textContent = CSS;
        document.head.appendChild(s);
    }

    function items(lista) {
        return Array.prototype.slice.call(lista.querySelectorAll(':scope > .nt-orden__item'));
    }

    function numerar(lista) {
        items(lista).forEach(function (li, i) {
            var p = li.querySelector('.nt-orden__pos');
            if (p) p.textContent = String(i + 1);
        });
        lista.dispatchEvent(new CustomEvent('nt-orden-change', { bubbles: true }));
    }

    function contenedorScroll(el) {
        var n = el.parentElement;
        while (n && n !== document.body) {
            var oy = getComputedStyle(n).overflowY;
            if ((oy === 'auto' || oy === 'scroll') && n.scrollHeight > n.clientHeight) return n;
            n = n.parentElement;
        }
        return null;
    }

    function mover(lista, li, delta) {
        var arr = items(lista);
        var i = arr.indexOf(li);
        var j = i + delta;
        if (j < 0 || j >= arr.length) return;
        if (delta < 0) lista.insertBefore(li, arr[j]);
        else lista.insertBefore(li, arr[j].nextSibling);
        numerar(lista);
    }

    function iniciarLista(lista) {
        if (lista.__ntOrden) return;
        lista.__ntOrden = true;
        lista.__original = items(lista);

        items(lista).forEach(function (li) { enlazar(lista, li); });
        numerar(lista);
    }

    function enlazar(lista, li) {
        var handle = li.querySelector('.nt-orden__handle');
        var up = li.querySelector('.nt-orden__up');
        var down = li.querySelector('.nt-orden__down');
        if (up) up.addEventListener('click', function () { mover(lista, li, -1); up.focus(); });
        if (down) down.addEventListener('click', function () { mover(lista, li, 1); down.focus(); });
        if (!handle) return;

        handle.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp') { e.preventDefault(); mover(lista, li, -1); handle.focus(); }
            if (e.key === 'ArrowDown') { e.preventDefault(); mover(lista, li, 1); handle.focus(); }
        });

        var arrastrando = false;
        handle.addEventListener('pointerdown', function (e) {
            if (e.button !== undefined && e.button !== 0) return;
            e.preventDefault();
            arrastrando = true;
            li.classList.add('is-dragging');
            try { handle.setPointerCapture(e.pointerId); } catch (err) { /* sin captura */ }
        });
        handle.addEventListener('pointermove', function (e) {
            if (!arrastrando) return;
            var sobre = document.elementFromPoint(e.clientX, e.clientY);
            var destino = sobre && sobre.closest ? sobre.closest('.nt-orden__item') : null;
            if (destino && destino !== li && destino.parentNode === lista) {
                var r = destino.getBoundingClientRect();
                var despues = e.clientY > r.top + r.height / 2;
                lista.insertBefore(li, despues ? destino.nextSibling : destino);
            }
            // Autodesplazamiento cerca de los bordes
            var sc = contenedorScroll(lista);
            if (sc) {
                var b = sc.getBoundingClientRect();
                if (e.clientY < b.top + 40) sc.scrollTop -= 14;
                else if (e.clientY > b.bottom - 40) sc.scrollTop += 14;
            } else {
                if (e.clientY < 60) window.scrollBy(0, -14);
                else if (e.clientY > window.innerHeight - 60) window.scrollBy(0, 14);
            }
        });
        function terminar() {
            if (!arrastrando) return;
            arrastrando = false;
            li.classList.remove('is-dragging');
            numerar(lista);
        }
        handle.addEventListener('pointerup', terminar);
        handle.addEventListener('pointercancel', terminar);
        handle.addEventListener('lostpointercapture', terminar);
    }

    function ordenarAZ(lista) {
        var arr = items(lista);
        arr.sort(function (a, b) {
            var na = (a.getAttribute('data-nombre') || a.textContent).trim();
            var nb = (b.getAttribute('data-nombre') || b.textContent).trim();
            return na.localeCompare(nb, 'es', { sensitivity: 'base' });
        });
        arr.forEach(function (li) { lista.appendChild(li); });
        numerar(lista);
    }

    function volverOriginal(lista) {
        (lista.__original || []).forEach(function (li) { lista.appendChild(li); });
        numerar(lista);
    }

    function objetivo(btn, attr) {
        var sel = btn.getAttribute(attr);
        return sel ? document.querySelector(sel) : null;
    }

    document.addEventListener('click', function (e) {
        var az = e.target.closest ? e.target.closest('[data-nt-orden-az]') : null;
        if (az) { var l1 = objetivo(az, 'data-nt-orden-az'); if (l1) ordenarAZ(l1); return; }
        var rs = e.target.closest ? e.target.closest('[data-nt-orden-reset]') : null;
        if (rs) { var l2 = objetivo(rs, 'data-nt-orden-reset'); if (l2) volverOriginal(l2); }
    });

    function iniciarTodo() {
        inyectarCss();
        Array.prototype.forEach.call(document.querySelectorAll('[data-nt-orden]'), iniciarLista);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciarTodo);
    else iniciarTodo();

    window.NtOrden = { iniciar: iniciarTodo, numerar: numerar, ordenarAZ: ordenarAZ, volverOriginal: volverOriginal };
})();
