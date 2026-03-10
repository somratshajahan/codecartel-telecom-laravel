import './bootstrap';
import NProgress from 'nprogress';
import 'nprogress/nprogress.css';

NProgress.configure({ showSpinner: false });

const FIREBASE_VERSION = '10.13.2';
const THEME_STORAGE_KEY = 'cc_theme';
const LIGHT_THEME = 'light';
const DARK_THEME = 'dark';

function storedTheme() {
    try {
        const theme = window.localStorage.getItem(THEME_STORAGE_KEY);

        return theme === DARK_THEME || theme === LIGHT_THEME ? theme : null;
    } catch (error) {
        return null;
    }
}

function preferredTheme() {
    const theme = storedTheme();

    if (theme) {
        return theme;
    }

    const mediaQuery = typeof window.matchMedia === 'function'
        ? window.matchMedia('(prefers-color-scheme: dark)')
        : null;

    return mediaQuery?.matches ? DARK_THEME : LIGHT_THEME;
}

function applyTheme(theme) {
    const nextTheme = theme === DARK_THEME ? DARK_THEME : LIGHT_THEME;

    document.documentElement.setAttribute('data-theme', nextTheme);
    document.documentElement.style.colorScheme = nextTheme;

    return nextTheme;
}

function syncThemeToggles(theme) {
    const isDark = theme === DARK_THEME;

    document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
        toggle.checked = isDark;
        toggle.setAttribute('aria-checked', isDark ? 'true' : 'false');
    });
}

function setTheme(theme, persist = true) {
    const nextTheme = applyTheme(theme);

    if (persist) {
        try {
            window.localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
        } catch (error) {
            // Ignore storage errors and keep the in-memory theme applied.
        }
    }

    syncThemeToggles(nextTheme);
}

function initializeThemeToggle() {
    const currentTheme = applyTheme(preferredTheme());

    syncThemeToggles(currentTheme);

    document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
        toggle.addEventListener('change', event => {
            setTheme(event.currentTarget.checked ? DARK_THEME : LIGHT_THEME);
        });
    });
}

function initializePasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        const inputId = button.dataset.passwordToggle;
        const input = inputId ? document.getElementById(inputId) : null;

        if (!input) {
            return;
        }

        const showIcon = button.querySelector('[data-password-toggle-show-icon]');
        const hideIcon = button.querySelector('[data-password-toggle-hide-icon]');

        const syncToggleState = isVisible => {
            button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
            button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
            showIcon?.classList.toggle('hidden', isVisible);
            hideIcon?.classList.toggle('hidden', !isVisible);
        };

        syncToggleState(input.type === 'text');

        button.addEventListener('click', () => {
            const isVisible = input.type === 'text';

            input.type = isVisible ? 'password' : 'text';
            syncToggleState(!isVisible);
        });
    });
}

applyTheme(preferredTheme());

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
    initializeThemeToggle();
    initializePasswordToggles();

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
