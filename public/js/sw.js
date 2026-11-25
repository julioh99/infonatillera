// public/js/sw.js - Service Worker de InfoNatillera (Web Push sin carga útil)
// El servidor envía un push VACÍO; aquí se consultan los avisos pendientes del socio y se muestran.
// Subir SW_VERSION cuando se cambie este archivo: el navegador lo detecta y el cliente avisa de la actualización.

var SW_VERSION = '2026-11-25.1';
var ICONO = '/img/natillera_la_union_logo.png';
var URL_PENDIENTES = '/socio/notificaciones-pendientes-json';
var URL_APP = '/socio/dashboard';
var MAX_INDIVIDUALES = 3;

self.addEventListener('install', function () {
    // Activa la versión nueva sin esperar a que se cierren las pestañas.
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('message', function (event) {
    if (event.data === 'version' && event.ports && event.ports[0]) {
        event.ports[0].postMessage(SW_VERSION);
    }
});

function opciones(cuerpo, tag, url) {
    return {
        body: cuerpo,
        icon: ICONO,
        badge: ICONO,
        tag: tag,
        renotify: false,
        data: { url: url || URL_APP }
    };
}

// Consulta los avisos pendientes; si no hay sesión o falla la red, devuelve null.
function consultarPendientes() {
    return fetch(URL_PENDIENTES, {
        credentials: 'same-origin',
        cache: 'no-store',
        redirect: 'manual',
        headers: { 'Accept': 'application/json' }
    }).then(function (res) {
        var tipo = res.headers.get('content-type') || '';
        if (!res.ok || tipo.indexOf('json') === -1) {
            return null;
        }
        return res.json();
    }).then(function (datos) {
        if (!datos || !datos.success || !Array.isArray(datos.pendientes)) {
            return null;
        }
        return datos.pendientes;
    }).catch(function () {
        return null;
    });
}

function mostrarAvisos() {
    return consultarPendientes().then(function (pendientes) {
        var reg = self.registration;
        if (!pendientes || pendientes.length === 0) {
            // userVisibleOnly obliga a mostrar algo siempre.
            return reg.showNotification('InfoNatillera', opciones('Tienes avisos nuevos de la natillera. Ábrela para verlos.', 'nt-general'));
        }
        // Los más recientes primero; si son muchos se agrupan en un solo aviso.
        var recientes = pendientes.slice(-MAX_INDIVIDUALES).reverse();
        var tareas = [];
        if (pendientes.length > MAX_INDIVIDUALES) {
            tareas.push(reg.showNotification(
                'InfoNatillera: ' + pendientes.length + ' avisos nuevos',
                opciones('Ábrela para leerlos todos.', 'nt-resumen')
            ));
        }
        recientes.forEach(function (n) {
            tareas.push(reg.showNotification(
                n.titulo || 'InfoNatillera',
                opciones(n.mensaje || 'Tienes una nueva notificación de la natillera.', 'nt-' + n.id)
            ));
        });
        return Promise.all(tareas);
    });
}

self.addEventListener('push', function (event) {
    event.waitUntil(mostrarAvisos());
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var destino = (event.notification.data && event.notification.data.url) || URL_APP;
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (ventanas) {
            for (var i = 0; i < ventanas.length; i++) {
                if (ventanas[i].url.indexOf(self.location.origin) === 0 && 'focus' in ventanas[i]) {
                    return ventanas[i].focus();
                }
            }
            return self.clients.openWindow(destino);
        })
    );
});

// Si el navegador renueva la suscripción por su cuenta, la página la vuelve a registrar al abrirse.
self.addEventListener('pushsubscriptionchange', function (event) {
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (ventanas) {
            ventanas.forEach(function (v) { v.postMessage({ tipo: 'push-resuscribir' }); });
        })
    );
});
