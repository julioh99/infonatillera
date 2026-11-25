/* public/js/ui.js · pulido de interfaz 2027. Solo presentación; sin dependencias.
 * Todo falla en silencio: si algo no es compatible, la página queda como está. */
(function () {
    'use strict';
    var d = document;
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    var IO = 'IntersectionObserver' in window;

    /* 1. Conteo animado de cifras (formato colombiano: punto de miles) */
    var RX = /^(\D*?)(\d{1,3}(?:\.\d{3})+|\d+)(\D*)$/;
    function fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function target(el) {
        var n = el.firstChild;
        while (n && !(n.nodeType === 3 && /\d/.test(n.nodeValue))) n = n.nextSibling;
        return n;
    }
    function count(node, m) {
        var end = parseInt(m[2].replace(/\./g, ''), 10), t0 = null, orig = node.nodeValue;
        function step(t) {
            if (t0 === null) t0 = t;
            var p = Math.min((t - t0) / 650, 1), e = 1 - Math.pow(1 - p, 3);
            node.nodeValue = p < 1 ? m[1] + fmt(Math.round(end * e)) + m[3] : orig;
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
    function initCount() {
        if (reduce || !IO) return;
        var els = d.querySelectorAll('[data-countup], .nt-count, .nt-hero .display-5, .nt-hero [class*="display"], .nt-stat__value');
        var io = new IntersectionObserver(function (list) {
            list.forEach(function (it) {
                if (!it.isIntersecting) return;
                io.unobserve(it.target);
                var node = it.target._ntNode, m = it.target._ntM;
                if (node && node.nodeValue === m.input) count(node, m);
            });
        }, { threshold: 0.2 });
        Array.prototype.forEach.call(els, function (el) {
            try {
                var node = target(el);
                if (!node) return;
                var m = RX.exec(node.nodeValue);
                if (!m) return;
                var v = parseInt(m[2].replace(/\./g, ''), 10);
                var money = /\$/.test(m[1] + m[3]) || m[2].indexOf('.') > -1 || el.hasAttribute('data-countup');
                if (!(v > 0) || (!money && !el.matches('.nt-stat__value, .nt-count'))) return;
                el._ntNode = node; el._ntM = m;
                io.observe(el);
            } catch (e) { /* deja el valor original */ }
        });
    }

    /* 2. Revelado al hacer scroll para bloques fuera de pantalla */
    function initReveal() {
        if (reduce || !IO) return;
        var vh = window.innerHeight, list = [];
        Array.prototype.forEach.call(d.querySelectorAll('main .card, main .nt-stat, main .gd-surface, main .gg-panel, main .gg-act, main .nt-hero'), function (el) {
            if (el.closest('.modal, .offcanvas, .nt-pre') || el.getBoundingClientRect().top < vh) return;
            el.classList.add('nt-pre'); list.push(el);
        });
        if (!list.length) return;
        var io = new IntersectionObserver(function (items) {
            items.forEach(function (it) {
                if (!it.isIntersecting) return;
                var el = it.target; io.unobserve(el);
                el.classList.add('nt-in');
                setTimeout(function () { el.classList.remove('nt-pre', 'nt-in'); }, 360);
            });
        }, { rootMargin: '0px 0px -6% 0px' });
        list.forEach(function (el) { io.observe(el); });
        /* Respaldo: nada se queda oculto */
        setTimeout(function () { list.forEach(function (el) { el.classList.remove('nt-pre', 'nt-in'); }); }, 4000);
    }

    /* 3. Botones de envío ocupados (aria-busy, sin tocar el texto) */
    function initBusy() {
        d.addEventListener('submit', function (e) {
            var f = e.target;
            setTimeout(function () {
                if (e.defaultPrevented || !f || !f.querySelectorAll) return;
                var b = e.submitter || f.querySelector('[type="submit"]');
                if (!b || b.getAttribute('aria-busy') === 'true') return;
                b.setAttribute('aria-busy', 'true');
                setTimeout(function () { b.removeAttribute('aria-busy'); }, 10000);
            }, 0);
        });
        window.addEventListener('pageshow', function () {
            Array.prototype.forEach.call(d.querySelectorAll('[aria-busy="true"]'), function (b) { b.removeAttribute('aria-busy'); });
        });
    }

    /* 4. Onda sutil al presionar botones */
    function initRipple() {
        if (reduce) return;
        d.addEventListener('pointerdown', function (e) {
            var b = e.target.closest && e.target.closest('.btn:not(:disabled):not(.disabled):not(.btn-link)');
            if (!b || b.hasAttribute('data-bs-toggle') && b.getAttribute('data-bs-toggle') === 'dropdown') return;
            var r = b.getBoundingClientRect(), s = Math.max(r.width, r.height) * 2, o = d.createElement('span');
            o.className = 'nt-ripple';
            o.style.cssText = 'width:' + s + 'px;height:' + s + 'px;left:' + (e.clientX - r.left - s / 2) + 'px;top:' + (e.clientY - r.top - s / 2) + 'px';
            b.classList.add('nt-rippling'); b.appendChild(o);
            setTimeout(function () { o.remove(); if (!b.querySelector('.nt-ripple')) b.classList.remove('nt-rippling'); }, 320);
        }, { passive: true });
    }

    function run() {
        [initCount, initReveal, initBusy, initRipple].forEach(function (f) { try { f(); } catch (e) { /* ignora */ } });
    }
    if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', run); else run();
})();
