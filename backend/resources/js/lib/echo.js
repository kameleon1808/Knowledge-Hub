import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

/**
 * Returns a configured Echo instance for Reverb, or null if broadcasting
 * is not configured (e.g. missing env vars). Only call when authenticated
 * so private channel auth works. Guests should not call this.
 */
export function getEcho() {
    const key = import.meta.env.VITE_REVERB_APP_KEY || 'local-key';
    let host = import.meta.env.VITE_REVERB_HOST || 'localhost';
    let port = Number(import.meta.env.VITE_REVERB_PORT) || 8081;
    const scheme = (import.meta.env.VITE_REVERB_SCHEME || 'http').toLowerCase();

    if (!key || !host) {
        return null;
    }

    if (host === 'localhost' && port === 8080) {
        port = 8081;
    }

    if (echoInstance) {
        return echoInstance;
    }

    window.Pusher = Pusher;

    const useTLS = scheme === 'https';
    echoInstance = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: useTLS,
        enabledTransports: useTLS ? ['wss', 'ws'] : ['ws', 'wss'],
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        },
    });

    return echoInstance;
}
