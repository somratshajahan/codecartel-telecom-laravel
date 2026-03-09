import './bootstrap';
import NProgress from 'nprogress';
import 'nprogress/nprogress.css';

NProgress.configure({ showSpinner: false });

const FIREBASE_VERSION = '10.13.2';

function loadExternalScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[data-src="${src}"]`);

        if (existing?.dataset.loaded === 'true') {
            resolve();
            return;
        }

        if (existing) {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error(`Failed to load ${src}`)), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.dataset.src = src;
        script.onload = () => {
            script.dataset.loaded = 'true';
            resolve();
        };
        script.onerror = () => reject(new Error(`Failed to load ${src}`));
        document.head.appendChild(script);
    });
}

async function ensureFirebaseCompat() {
    if (window.firebase?.messaging) {
        return window.firebase;
    }

    await loadExternalScript(`https://www.gstatic.com/firebasejs/${FIREBASE_VERSION}/firebase-app-compat.js`);
    await loadExternalScript(`https://www.gstatic.com/firebasejs/${FIREBASE_VERSION}/firebase-messaging-compat.js`);

    return window.firebase;
}

function showForegroundNotification(payload) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    const notification = payload?.notification || {};
    const data = payload?.data || {};
    const title = notification.title || data.title || 'Notification';
    const body = notification.body || data.body || '';
    const link = data.link || '/';
    const icon = notification.icon || data.icon || '/favicon.ico';
    const popup = new Notification(title, { body, icon });

    popup.onclick = () => {
        window.focus();
        window.location.href = link;
        popup.close();
    };
}

async function initializeFirebasePush() {
    if (window.__firebasePushInit) {
        return;
    }

    window.__firebasePushInit = true;

    if (!('serviceWorker' in navigator) || !('Notification' in window) || !window.axios) {
        return;
    }

    try {
        const { data } = await window.axios.get('/notifications/firebase/bootstrap');

        if (!data?.authenticated || !data?.enabled || !data?.config || !data?.vapidKey) {
            return;
        }

        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', {
            scope: '/',
        });

        const firebase = await ensureFirebaseCompat();

        if (!firebase?.apps?.length) {
            firebase.initializeApp(data.config);
        }

        const messaging = firebase.messaging();

        if (Notification.permission === 'default') {
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                return;
            }
        }

        if (Notification.permission !== 'granted') {
            return;
        }

        const token = await messaging.getToken({
            vapidKey: data.vapidKey,
            serviceWorkerRegistration: registration,
        });

        if (token) {
            const storageKey = `firebase_push_token_${data.userId}`;

            if (window.localStorage.getItem(storageKey) !== token) {
                await window.axios.post('/notifications/firebase/token', {
                    fcm_token: token,
                });

                window.localStorage.setItem(storageKey, token);
            }
        }

        messaging.onMessage((payload) => {
            showForegroundNotification(payload);
        });
    } catch (error) {
        console.warn('Firebase push initialization failed.', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            NProgress.start();
        });
    });

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            NProgress.start();
        });
    });

    window.addEventListener('load', () => {
        NProgress.done();
    });

    initializeFirebasePush();
});
