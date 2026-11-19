// Contact Scan service worker: shows car alerts and handles one-tap replies.

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'Contact Scan', body: event.data ? event.data.text() : '' };
    }

    const actions = (data.actions || []).map((a) => ({ action: a.action, title: a.title }));

    event.waitUntil(
        self.registration.showNotification(data.title || 'Someone needs you to move your car', {
            body: data.body || '',
            icon: '/app-icons/icon-192.png',
            badge: '/app-icons/badge-96.png',
            tag: data.tag,
            renotify: true,
            requireInteraction: true,
            vibrate: [200, 100, 200, 100, 400],
            actions,
            data,
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    const data = event.notification.data || {};
    event.notification.close();

    // Action button tapped: send the reply straight away.
    const action = (data.actions || []).find((a) => a.action === event.action);
    if (action && action.url) {
        event.waitUntil(
            fetch(action.url, { method: 'POST', headers: { Accept: 'application/json' }, credentials: 'omit' })
                .then(() => self.registration.showNotification('Reply sent ✅', { body: action.title, tag: data.tag, icon: '/app-icons/icon-192.png' }))
                .catch(() => self.clients.openWindow(data.url || '/dashboard'))
        );
        return;
    }

    const url = data.url || '/dashboard';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((wins) => {
            for (const w of wins) {
                if (w.url === url && 'focus' in w) return w.focus();
            }
            return self.clients.openWindow(url);
        })
    );
});
