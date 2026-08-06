// public/js/sw.js - Service Worker para Notificaciones Web Push

self.addEventListener('push', function(event) {
    if (event.data) {
        const payload = event.data.json();
        const options = {
            body: payload.mensaje || 'Tienes una nueva notificación de la Natillera.',
            icon: '/icon.png',
            badge: '/badge.png',
            vibrate: [100, 50, 100],
            data: {
                dateOfArrival: Date.now(),
                primaryKey: 1
            }
        };

        event.waitUntil(
            self.registration.showNotification(payload.titulo || 'InfoNatillera', options)
        );
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/socio/dashboard')
    );
});
