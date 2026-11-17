import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

/*
 | Dashboard charts (step 3.5). One series per chart, so one color (Dark Azure) and no legend:
 | the card title names the series. Thin marks, recessive grid, hover tooltips.
 | Usage (see components/chart.blade.php): x-data="chart({ type: 'bar', labels: [...], values: [...], label: '...' })"
 */
const ink = '#141414';
const muted = '#64748B';
const line = '#E3E9ED';
const azure = '#00638E';

// Use the app's font (Figtree) and muted ink in every chart.
Chart.defaults.font.family = "Figtree, ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = muted;

Alpine.data('chart', ({ type, labels, values, label }) => ({
    init() {
        const isBar = type === 'bar';

        new Chart(this.$refs.canvas, {
            type,
            data: {
                labels,
                datasets: [{
                    label,
                    data: values,
                    backgroundColor: isBar ? azure : 'rgba(0, 99, 142, 0.08)',
                    borderColor: azure,
                    // Bars: thin, 4px rounded at the data end only, anchored to the baseline.
                    borderRadius: isBar ? 4 : 0,
                    borderSkipped: 'start',
                    maxBarThickness: 18,
                    // Line: 2px, no dots until hover (8px).
                    borderWidth: isBar ? 0 : 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: azure,
                    pointHoverBorderColor: '#FFFFFF',
                    pointHoverBorderWidth: 2,
                    fill: !isBar,
                    // "monotone" smooths the line without overshooting: it never dips below or above the real values.
                    cubicInterpolationMode: 'monotone',
                }],
            },
            options: {
                indexAxis: isBar ? 'y' : 'x', // horizontal bars: long item names stay readable
                maintainAspectRatio: false,
                interaction: isBar ? { mode: 'nearest', intersect: true } : { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: ink,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: { label: (ctx) => `${ctx.formattedValue} ${label.toLowerCase()}` },
                    },
                },
                scales: {
                    x: {
                        grid: { color: isBar ? line : 'transparent', drawTicks: false },
                        border: { display: false },
                        ticks: { color: muted, padding: 8, precision: 0, maxRotation: 0, autoSkipPadding: 12 },
                        beginAtZero: true,
                    },
                    y: {
                        grid: { color: isBar ? 'transparent' : line, drawTicks: false },
                        border: { display: false },
                        ticks: { color: isBar ? ink : muted, padding: 8, precision: 0 },
                        beginAtZero: true,
                    },
                },
            },
        });
    },
}));

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
