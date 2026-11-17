// Keep the administration editor out of the initial entrypoint budget.
import('../src/admin.js').catch((error) => {
    console.error('IntraVox administration could not be loaded', error);
    const target = document.getElementById('intravox-admin-settings');
    if (target) {
        target.textContent = 'IntraVox could not load. Refresh the page to try again.';
        target.setAttribute('role', 'alert');
    }
});
