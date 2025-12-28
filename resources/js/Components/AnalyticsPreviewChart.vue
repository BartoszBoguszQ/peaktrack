<script setup>
import { computed, ref } from 'vue'
import { Line } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend)

const mode = ref('weekly')

const weekly = [
    { label: '03.11 - 09.11', distance_km: 18.2, calories: 1450 },
    { label: '10.11 - 16.11', distance_km: 22.5, calories: 1760 },
    { label: '17.11 - 23.11', distance_km: 14.4, calories: 1180 },
    { label: '24.11 - 30.11', distance_km: 26.1, calories: 2010 },
    { label: '01.12 - 07.12', distance_km: 19.7, calories: 1560 },
    { label: '08.12 - 14.12', distance_km: 28.3, calories: 2140 },
    { label: '15.12 - 21.12', distance_km: 24.8, calories: 1890 },
    { label: '22.12 - 28.12', distance_km: 31.2, calories: 2380 },
]

const monthly = [
    { label: '08.2025', distance_km: 82.4, calories: 6350 },
    { label: '09.2025', distance_km: 96.8, calories: 7210 },
    { label: '10.2025', distance_km: 88.1, calories: 6740 },
    { label: '11.2025', distance_km: 104.6, calories: 7920 },
    { label: '12.2025', distance_km: 91.3, calories: 7020 },
]

const axisColor = '#9ca3af'
const gridColor = 'rgba(148, 163, 184, 0.25)'

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            labels: {
                color: axisColor,
                usePointStyle: true,
                pointStyle: 'circle',
            },
        },
        tooltip: {
            mode: 'index',
            intersect: false,
        },
    },
    scales: {
        x: {
            ticks: { color: axisColor },
            grid: { color: gridColor },
        },
        y: {
            beginAtZero: true,
            ticks: { color: axisColor },
            grid: { color: gridColor },
        },
        y1: {
            beginAtZero: true,
            position: 'right',
            ticks: { color: axisColor },
            grid: { drawOnChartArea: false, color: gridColor },
        },
    },
}

const weeklyChartData = computed(() => ({
    labels: weekly.map(w => w.label),
    datasets: [
        {
            label: 'Distance [km]',
            data: weekly.map(w => w.distance_km),
            tension: 0.3,
            borderColor: '#3b82f6',
            backgroundColor: '#3b82f6',
            borderWidth: 2.5,
            pointRadius: 3,
            pointHoverRadius: 4,
        },
        {
            label: 'Calories',
            data: weekly.map(w => w.calories),
            yAxisID: 'y1',
            tension: 0.3,
            borderColor: '#f97316',
            backgroundColor: '#f97316',
            borderWidth: 2.5,
            pointRadius: 3,
            pointHoverRadius: 4,
        },
    ],
}))

const monthlyChartData = computed(() => ({
    labels: monthly.map(m => m.label),
    datasets: [
        {
            label: 'Distance [km]',
            data: monthly.map(m => m.distance_km),
            tension: 0.3,
            borderColor: '#3b82f6',
            backgroundColor: '#3b82f6',
            borderWidth: 2.5,
            pointRadius: 3,
            pointHoverRadius: 4,
        },
        {
            label: 'Calories',
            data: monthly.map(m => m.calories),
            yAxisID: 'y1',
            tension: 0.3,
            borderColor: '#f97316',
            backgroundColor: '#f97316',
            borderWidth: 2.5,
            pointRadius: 3,
            pointHoverRadius: 4,
        },
    ],
}))
</script>

<template>
    <div class="h-full w-full p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white">Analytics preview</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Distance + calories</div>
            </div>

            <div class="inline-flex rounded-xl bg-gray-100 dark:bg-gray-800 p-1">
                <button
                    type="button"
                    class="px-3 py-1 text-xs rounded-lg"
                    :class="mode === 'weekly'
                        ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow'
                        : 'text-gray-600 dark:text-gray-300'"
                    @click="mode = 'weekly'"
                >
                    Weekly
                </button>
                <button
                    type="button"
                    class="px-3 py-1 text-xs rounded-lg"
                    :class="mode === 'monthly'
                        ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow'
                        : 'text-gray-600 dark:text-gray-300'"
                    @click="mode = 'monthly'"
                >
                    Monthly
                </button>
            </div>
        </div>

        <div class="mt-4 h-[260px]">
            <Line
                v-if="mode === 'weekly'"
                :data="weeklyChartData"
                :options="chartOptions"
            />
            <Line
                v-else
                :data="monthlyChartData"
                :options="chartOptions"
            />
        </div>
    </div>
</template>
