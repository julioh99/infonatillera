// public/js/push_client.js - Activación de notificaciones del navegador (Web Push)
// Usa los elementos de admin/notificaciones: #btnActivarNotifications (data-vapid-key),
// #btnProbarPush y #pushEstado (opcionales). Sin animaciones.

(function () {
    'use strict';

    var RUTA_SW = '/js/sw.js';

    function aviso(titulo, texto, icono) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire(titulo, texto, icono || 'info');
        } else {
            window.alert(titulo + '\n\n' + texto);
        }
    }

    function soportado() {
        return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    }

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var salida = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            salida[i] = rawData.charCodeAt(i);
        }
        return salida;
    }

    function pintarEstado(texto, clase) {
        var el = document.getElementById('pushEstado');
        if (!el) { return; }
        el.textContent = texto;
        el.className = 'badge ' + (clase || 'bg-secondary');
    }

    // Registra (o actualiza) el service worker y busca una versión nueva.
    function registrarSW() {
        return navigator.serviceWorker.register(RUTA_SW).then(function (reg) {
            try { reg.update(); } catch (e) { /* sin red: se usa la versión instalada */ }
            return navigator.serviceWorker.ready.then(function () { return reg; });
        });
    }

    function enviarSuscripcion(suscripcion) {
        return fetch('/admin/notificaciones/suscribir', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(suscripcion)
        }).then(function (res) {
            return res.json().catch(function () {
                throw new Error('El servidor no pudo registrar este dispositivo. Intenta de nuevo en un momento.');
            });
        });
    }

    function textoPermiso() {
        if (Notification.permission === 'denied') {
            return 'Bloqueadas en este navegador';
        }
        return Notification.permission === 'granted' ? 'Permiso concedido' : 'Sin activar';
    }

    function activar(llavePublica) {
        if (!soportado()) {
            pintarEstado('No disponible en este navegador', 'bg-secondary');
            aviso('Notificaciones dentro de la app',
                'Este navegador o dispositivo no permite avisos nativos (por ejemplo Safari en iPhone sin instalar la app, o el modo incógnito). ' +
                'No te preocupes: todos los avisos están en la campanita de la plataforma y los puedes compartir por WhatsApp.', 'info');
            return Promise.resolve();
        }
        if (!llavePublica) {
            aviso('Notificaciones no disponibles', 'El servidor aún no tiene configuradas las llaves de notificaciones. Avisa a la directiva.', 'warning');
            return Promise.resolve();
        }
        if (Notification.permission === 'denied') {
            pintarEstado('Bloqueadas en este navegador', 'bg-danger');
            aviso('Notificaciones bloqueadas',
                'Bloqueaste las notificaciones de este sitio. Para activarlas, toca el candado junto a la dirección de la página, ' +
                'cambia "Notificaciones" a "Permitir" y vuelve a pulsar este botón.', 'warning');
            return Promise.resolve();
        }

        return registrarSW().then(function (reg) {
            return Notification.requestPermission().then(function (permiso) {
                if (permiso !== 'granted') {
                    pintarEstado(permiso === 'denied' ? 'Bloqueadas en este navegador' : 'Sin activar', permiso === 'denied' ? 'bg-danger' : 'bg-secondary');
                    var e = new Error(permiso === 'denied'
                        ? 'No permitiste las notificaciones. Si cambias de opinión, habilítalas desde el candado junto a la dirección de la página.'
                        : 'No elegiste una opción. Pulsa el botón de nuevo cuando quieras activarlas.');
                    e.esPermiso = true;
                    throw e;
                }
                return reg.pushManager.getSubscription().then(function (existente) {
                    if (existente) { return existente; }
                    return reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(llavePublica)
                    });
                });
            });
        }).then(enviarSuscripcion).then(function (data) {
            if (data && data.success) {
                pintarEstado('Activadas en este dispositivo', 'bg-success');
                aviso('Notificaciones activadas', 'Este dispositivo recibirá los avisos de la natillera. Puedes pulsar "Enviarme una prueba" para comprobarlo.', 'success');
            } else {
                aviso('No se pudo activar', (data && data.message) || 'Intenta de nuevo.', 'error');
            }
        }).catch(function (err) {
            console.error(err);
            if (err && err.esPermiso) {
                aviso('Notificaciones sin activar', err.message, 'info');
            } else {
                pintarEstado('No se pudo activar', 'bg-warning text-dark');
                aviso('No se pudo activar',
                    'Este navegador no completó la activación. Los avisos siguen disponibles en la campanita de la plataforma y por WhatsApp.', 'info');
            }
        });
    }

    function probar() {
        return fetch('/admin/notificaciones/prueba', { method: 'POST', credentials: 'same-origin' })
            .then(function (res) {
                return res.json().catch(function () { return { success: false, message: 'No se pudo enviar la prueba.' }; });
            })
            .then(function (data) {
                aviso(data.success ? 'Prueba enviada' : 'No se pudo enviar la prueba', data.message || '', data.success ? 'success' : 'warning');
            })
            .catch(function () {
                aviso('Sin conexión', 'No se pudo contactar al servidor. Revisa tu internet e intenta de nuevo.', 'error');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btnActivar = document.getElementById('btnActivarNotifications');
        var btnProbar = document.getElementById('btnProbarPush');

        // En cualquier página: dejar el service worker instalado y actualizado si ya hay permiso.
        if (soportado() && Notification.permission === 'granted') {
            registrarSW().catch(function () { /* se reintenta en la próxima visita */ });
        }
        if (soportado()) {
            navigator.serviceWorker.addEventListener('message', function (e) {
                if (e.data && e.data.tipo === 'push-resuscribir' && btnActivar) {
                    activar(btnActivar.getAttribute('data-vapid-key'));
                }
            });
        }

        if (!btnActivar) { return; }

        if (!soportado()) {
            pintarEstado('No disponible en este navegador', 'bg-secondary');
        } else {
            pintarEstado(textoPermiso(), Notification.permission === 'granted' ? 'bg-success' : (Notification.permission === 'denied' ? 'bg-danger' : 'bg-secondary'));
        }

        btnActivar.addEventListener('click', function () {
            activar(btnActivar.getAttribute('data-vapid-key'));
        });
        if (btnProbar) {
            btnProbar.addEventListener('click', probar);
        }
    });
})();
