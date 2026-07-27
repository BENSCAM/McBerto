import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

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
