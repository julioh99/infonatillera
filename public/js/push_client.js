// public/js/push_client.js

document.addEventListener('DOMContentLoaded', () => {
    const btnActivar = document.getElementById('btnActivarNotifications');

    if (btnActivar) {
        btnActivar.addEventListener('click', () => {
            const vapidPublicKey = btnActivar.getAttribute('data-vapid-key');
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                navigator.serviceWorker.register('/js/sw.js')
                .then(registration => {
                    return Notification.requestPermission().then(permission => {
                        if (permission === 'granted') {
                            return registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                            });
                        } else {
                            throw new Error('Permiso de notificaciones denegado.');
                        }
                    });
                })
                .then(subscription => {
                    return fetch('/admin/notificaciones/suscribir', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(subscription)
                    });
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Notificaciones Activadas', 'Tu dispositivo recibirá notificaciones nativas de la natillera.', 'success');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        title: 'Aviso de Notificaciones',
                        text: err.message || 'No se pudo activar la suscripción en este navegador. Recuerda que todas las notificaciones están disponibles dentro de la app en la Campanita 🔔 y las puedes compartir por WhatsApp.',
                        icon: 'info'
                    });
                });
            } else {
                Swal.fire({
                    title: 'Notificaciones en la App',
                    text: 'Este navegador o dispositivo no soporta Push nativo (como iOS Safari sin PWA o modo incógnito). ¡No te preocupes! Todas tus notificaciones están disponibles dentro de la plataforma en la Campanita 🔔 y las puedes compartir a WhatsApp.',
                    icon: 'info'
                });
            }
        });
    }
});

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
