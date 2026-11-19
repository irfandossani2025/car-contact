// Free web push: registers the service worker and saves this device's subscription.

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

export function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
}

export function pushManager(vapidKey, subscribeUrl, unsubscribeUrl) {
    return {
        state: 'working',
        error: '',

        async init() {
            const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
            const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;

            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                this.state = isIos && !standalone ? 'ios-install' : 'unsupported';
                return;
            }
            if (Notification.permission === 'denied') {
                this.state = 'denied';
                return;
            }
            try {
                const reg = await navigator.serviceWorker.register('/sw.js');
                const sub = await reg.pushManager.getSubscription();
                this.state = sub ? 'on' : 'off';
                // Keep the server in sync in case it lost this device.
                if (sub) this.save(sub);
            } catch (e) {
                this.state = 'off';
            }
        },

        async enable() {
            this.error = '';
            this.state = 'working';
            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    this.state = permission === 'denied' ? 'denied' : 'off';
                    return;
                }
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidKey),
                });
                await this.save(sub);
                this.state = 'on';
                setTimeout(() => window.location.reload(), 600);
            } catch (e) {
                this.error = 'Could not turn on notifications: ' + (e.message || e);
                this.state = 'off';
            }
        },

        async disable() {
            this.state = 'working';
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    await fetch(unsubscribeUrl, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                        body: JSON.stringify({ endpoint: sub.endpoint }),
                    });
                    await sub.unsubscribe();
                }
                window.location.reload();
            } catch (e) {
                this.state = 'on';
            }
        },

        async save(sub) {
            const json = sub.toJSON();
            const encodings = window.PushManager.supportedContentEncodings || ['aes128gcm'];
            const res = await fetch(subscribeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ ...json, contentEncoding: encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm' }),
            });
            if (!res.ok) throw new Error('server refused subscription');
        },
    };
}
