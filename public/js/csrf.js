/* public/js/csrf.js
 * Proteccion CSRF del lado del navegador:
 *  - agrega <input name="csrf_token"> a todo formulario POST que no lo tenga (tambien los creados por JS),
 *  - agrega la cabecera X-CSRF-Token a fetch y XMLHttpRequest same-origin que no sean GET/HEAD/OPTIONS.
 * El token sale de <meta name="csrf-token">. Sin dependencias.
 */
(function () {
    'use strict';

    function token() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? (m.getAttribute('content') || '') : '';
    }

    function esMismoOrigen(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function esMetodoSeguro(metodo) {
        var m = String(metodo || 'GET').toUpperCase();
        return m === 'GET' || m === 'HEAD' || m === 'OPTIONS';
    }

    function asegurarCampo(form) {
        if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') return;
        var metodo = (form.getAttribute('method') || 'GET').toUpperCase();
        if (metodo !== 'POST') return;
        var accion = form.getAttribute('action') || window.location.href;
        if (!esMismoOrigen(accion)) return;
        var t = token();
        if (!t) return;
        var existente = form.querySelector('input[name="csrf_token"]');
        if (existente) {
            if (!existente.value) existente.value = t;
            return;
        }
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = t;
        form.appendChild(input);
    }

    // Formularios: captura del evento submit (cubre los creados por JS) y barrido al cargar
    document.addEventListener('submit', function (ev) {
        asegurarCampo(ev.target);
    }, true);

    function barrer() {
        var forms = document.querySelectorAll('form');
        for (var i = 0; i < forms.length; i++) asegurarCampo(forms[i]);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', barrer);
    } else {
        barrer();
    }

    // fetch
    if (window.fetch) {
        var fetchOriginal = window.fetch;
        window.fetch = function (input, init) {
            try {
                var url = (typeof input === 'string') ? input : (input && input.url) || '';
                var metodo = (init && init.method) || (input && typeof input !== 'string' && input.method) || 'GET';
                if (!esMetodoSeguro(metodo) && esMismoOrigen(url) && token()) {
                    init = init || {};
                    var headers = new Headers(init.headers || (input && typeof input !== 'string' ? input.headers : undefined) || {});
                    if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', token());
                    init.headers = headers;
                }
            } catch (e) { /* no bloquear la peticion */ }
            return fetchOriginal.call(this, input, init);
        };
    }

    // XMLHttpRequest
    if (window.XMLHttpRequest) {
        var abrir = XMLHttpRequest.prototype.open;
        var enviar = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function (metodo, url) {
            this.__csrf = { metodo: metodo, url: url };
            return abrir.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function () {
            try {
                var c = this.__csrf;
                if (c && !esMetodoSeguro(c.metodo) && esMismoOrigen(String(c.url)) && token()) {
                    this.setRequestHeader('X-CSRF-Token', token());
                }
            } catch (e) { /* no bloquear la peticion */ }
            return enviar.apply(this, arguments);
        };
    }
})();
