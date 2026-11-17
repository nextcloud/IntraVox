/* Browser-mediated session access only: never read or copy cookies/tokens. */
(() => {
    const button = document.getElementById('connect-session');
    const status = document.getElementById('status');
    const destination = '/index.php/login?preview_storage=1&redirect_url=%2Findex.php%2Fapps%2Fintravox%2F';
    const openFreshLogin = () => window.location.replace(destination);

    if (window.top === window.self) {
        status.textContent = 'This page connects the embedded Preview. You can continue to the normal sign-in here.';
        button.addEventListener('click', openFreshLogin);
        return;
    }
    if (typeof document.requestStorageAccess !== 'function'
        || typeof document.hasStorageAccess !== 'function') {
        status.textContent = 'This browser cannot request shared session access. Try the in-Preview sign-in link; otherwise a separate tab is required.';
        button.disabled = true;
        return;
    }

    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            // Must be called directly within the click gesture, before any await.
            await document.requestStorageAccess();
            status.textContent = 'Permission granted. Opening a fresh Nextcloud sign-in…';
            openFreshLogin();
        } catch (error) {
            status.textContent = 'Your browser or the Preview frame blocked session access. Allow this app’s cookies for replit.com in your browser’s site permissions, then retry. If the frame policy blocks access, only Replit can change it; use the sign-in tab meanwhile.';
            const reason = typeof error?.name === 'string' ? error.name : 'Access denied';
            const detail = typeof error?.message === 'string' ? error.message.slice(0, 240) : '';
            status.textContent += ` Browser response: ${reason}${detail ? ` — ${detail}` : ''}.`;
            button.disabled = false;
        }
    });

    document.hasStorageAccess().then((allowed) => {
        if (allowed) {
            status.textContent = 'Session access is already allowed. Opening Nextcloud…';
            openFreshLogin();
        } else {
            status.textContent = 'Click the button and allow access if prompted. Your existing signed-in tab can then be used here.';
        }
    }).catch(() => {
        status.textContent = 'Click the button to ask your browser for session access.';
    });
})();
