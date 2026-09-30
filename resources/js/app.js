import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Chart from 'chart.js/auto';

window.Chart = Chart;

const palette = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'];

// <div x-data="chart(config)"><canvas x-ref="canvas"></canvas></div>
Alpine.data('chart', (config) => ({
    instance: null,
    init() {
        const datasets = (config.datasets || []).map((d, i) => ({
            borderColor: d.color || palette[i % palette.length],
            backgroundColor: d.fill === false || config.type === 'line' || config.type === 'radar'
                ? (d.color || palette[i % palette.length]) + '22'
                : (d.colors || d.color || palette[i % palette.length]),
            tension: 0.35,
            pointRadius: 3,
            borderWidth: 2,
            fill: (config.type === 'line' || config.type === 'radar') && d.fill !== false,
            ...d,
        }));
        this.instance = new Chart(this.$refs.canvas, {
            type: config.type || 'line',
            data: { labels: config.labels || [], datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: datasets.length > 1 || config.type === 'doughnut', position: 'bottom' } },
                spanGaps: true,
                scales: ['doughnut', 'radar', 'pie'].includes(config.type) ? (config.type === 'radar' ? { r: { min: 0, max: 10, ticks: { stepSize: 2 } } } : {}) : {
                    y: { beginAtZero: config.beginAtZero ?? false, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } },
                },
                ...(config.options || {}),
            },
        });
    },
    destroy() { this.instance?.destroy(); },
}));

// Toast notifications: Livewire $this->dispatch('toast', message: '...', type: 'success')
Alpine.data('toaster', (initial) => ({
    toasts: [],
    init() {
        if (initial) this.add({ message: initial, type: 'success' });
        window.addEventListener('toast', (e) => this.add(e.detail));
    },
    add({ message, type = 'success' }) {
        const id = Date.now() + Math.random();
        this.toasts.push({ id, message, type });
        setTimeout(() => (this.toasts = this.toasts.filter((t) => t.id !== id)), 4000);
    },
}));

Livewire.start();
