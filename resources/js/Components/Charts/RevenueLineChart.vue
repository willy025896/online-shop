<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Filler,
} from 'chart.js';

Chart.register(LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler);

const props = defineProps({
    data: { type: Array, default: () => [] },
    label: { type: String, default: 'Revenue' },
});

const chartData = computed(() => ({
    labels: props.data.map((d) => d.date),
    datasets: [
        {
            label: props.label,
            data: props.data.map((d) => d.revenue),
            fill: true,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.1)',
            tension: 0.3,
            pointRadius: props.data.length <= 14 ? 3 : 0,
            pointHoverRadius: 5,
        },
    ],
}));

const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => `$${Number(ctx.parsed.y).toFixed(2)}`,
            },
        },
    },
    scales: {
        x: {
            ticks: {
                maxTicksLimit: 7,
                color: '#9ca3af',
                font: { size: 11 },
            },
            grid: { display: false },
        },
        y: {
            ticks: {
                color: '#9ca3af',
                font: { size: 11 },
                callback: (v) => `$${v}`,
            },
            grid: { color: 'rgba(156,163,175,0.15)' },
            beginAtZero: true,
        },
    },
}));
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
        <div class="h-56">
            <Line :data="chartData" :options="options" />
        </div>
    </div>
</template>
