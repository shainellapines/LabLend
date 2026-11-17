import Alpine from 'alpinejs';

window.Alpine = Alpine;

/*
 | Live availability hints for the reservation form (section 10.4).
 | Whenever the start/end time changes, ask the server which items and rooms are already taken
 | in that slot (GET /reservations/availability) and mark them in the pickers.
 | The server still checks everything again on submit; this only warns the user early.
 */
Alpine.data('reservationAvailability', ({ url, ignore }) => ({
    taken: { equipment: {}, rooms: {} },
    status: 'idle', // idle | loading | done | error
    timer: null,

    init() {
        this.check();
    },

    // Wait 300 ms after the last change so we do not call the server on every keystroke.
    schedule() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.check(), 300);
    },

    async check() {
        const start = this.$root.querySelector('[name=start_at]').value;
        const end = this.$root.querySelector('[name=end_at]').value;
        if (!start || !end || end <= start) {
            this.taken = { equipment: {}, rooms: {} };
            this.status = 'idle';
            return;
        }

        this.status = 'loading';
        const params = new URLSearchParams({ start_at: start, end_at: end });
        if (ignore) params.append('ignore', ignore);

        try {
            const response = await fetch(`${url}?${params}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(response.status);
            this.taken = await response.json();
            this.status = 'done';
        } catch (e) {
            this.status = 'error';
        }
    },

    isTaken(type, id) {
        return Boolean(this.taken[type] && this.taken[type][id]);
    },

    get takenCount() {
        return Object.keys(this.taken.equipment || {}).length + Object.keys(this.taken.rooms || {}).length;
    },
}));

Alpine.start();
