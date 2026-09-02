import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

function sessionNotice() {
    let notice = document.getElementById('session-status-notice');

    if (notice) {
        return notice;
    }

    notice = document.createElement('div');
    notice.id = 'session-status-notice';
    notice.className = 'fixed bottom-4 right-4 z-[120] hidden max-w-sm rounded-md bg-gray-900 px-4 py-3 text-sm font-medium text-white shadow-lg';
    document.body.appendChild(notice);

    return notice;
}

function showSessionNotice(message) {
    const notice = sessionNotice();
    notice.textContent = message;
    notice.classList.remove('hidden');
}

function hideSessionNotice() {
    document.getElementById('session-status-notice')?.classList.add('hidden');
}

function closeLivewireErrorDialogs() {
    document.querySelectorAll('dialog[open]').forEach((dialog) => {
        try {
            dialog.close();
        } catch (error) {}
    });
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {});
    });
}

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();
                closeLivewireErrorDialogs();
                showSessionNotice('Session expirée. Rechargement en cours...');
                window.setTimeout(() => window.location.reload(), 800);

                return;
            }

            if (status === 0 || !navigator.onLine) {
                preventDefault();
                closeLivewireErrorDialogs();
                showSessionNotice('Connexion interrompue. La page se remettra à jour au retour du réseau.');
            }
        });
    });
});

window.addEventListener('offline', () => {
    closeLivewireErrorDialogs();
    showSessionNotice('Connexion interrompue. Vérifiez le réseau.');
});

window.addEventListener('online', () => {
    hideSessionNotice();
    window.location.reload();
});

document.addEventListener('alpine:init', () => {
    window.Alpine.store('confirmModal', {
        show: false,
        message: '',
        action: null,

        open(message, action) {
            this.message = message;
            this.action = action;
            this.show = true;
        },

        confirm() {
            const action = this.action;
            this.show = false;
            this.action = null;
            if (action) action();
        },

        cancel() {
            this.show = false;
            this.action = null;
        },
    });
});
