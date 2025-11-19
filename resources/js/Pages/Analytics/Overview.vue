<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
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

const props = defineProps({
    weekly: { type: Array, required: true },
    monthly: { type: Array, required: true },
})

const mode = ref('weekly')

const weeklyChartData = computed(() => ({
    labels: props.weekly.map(w => w.label),
    datasets: [
        {
            label: 'Distance [km]',
            data: props.weekly.map(w => w.distance_km),
            tension: 0.3,
        },
        {
            label: 'Calories',
            data: props.weekly.map(w => w.calories),
            yAxisID: 'y1',
            tension: 0.3,
        },
    ],
}))

const monthlyChartData = computed(() => ({
    labels: props.monthly.map(m => m.label),
    datasets: [
        {
            label: 'Distance [km]',
            data: props.monthly.map(m => m.distance_km),
            tension: 0.3,
        },
        {
            label: 'Calories',
            data: props.monthly.map(m => m.calories),
            yAxisID: 'y1',
            tension: 0.3,
        },
    ],
}))

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    scales: {
        y: {
            beginAtZero: true,
        },
        y1: {
            beginAtZero: true,
            position: 'right',
            grid: {
                drawOnChartArea: false,
            },
        },
    },
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Analytics" />
        <div class="py-6">
            <div class="max-w-6xl mx-auto px-4 space-y-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics</h1>
                    <div class="inline-flex rounded-xl bg-gray-100 dark:bg-gray-800 p-1">
                        <button
                            type="button"
                            class="px-3 py-1 text-sm rounded-lg"
                            :class="mode === 'weekly'
                                ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow'
                                : 'text-gray-600 dark:text-gray-300'"
                            @click="mode = 'weekly'"
                        >
                            Weekly
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1 text-sm rounded-lg"
                            :class="mode === 'monthly'
                                ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow'
                                : 'text-gray-600 dark:text-gray-300'"
                            @click="mode = 'monthly'"
                        >
                            Monthly
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Total workouts (last 8 weeks)</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ props.weekly.reduce((acc, w) => acc + w.workouts, 0) }}
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Total distance [km]</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ props.weekly.reduce((acc, w) => acc + w.distance_km, 0).toFixed(1) }}
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Total calories</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ props.weekly.reduce((acc, w) => acc + w.calories, 0) }}
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 h-80">
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
        </div>
    </AuthenticatedLayout>
</template>
