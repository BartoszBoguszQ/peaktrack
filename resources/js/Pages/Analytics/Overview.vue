<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
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
    filters: {
        type: Object,
        required: true,
    },
})

const mode = ref('weekly')

const selectedTypes = ref([...props.filters.selected_types])

const allTypesSelected = computed(() => {
    return props.filters.available_types.length > 0 &&
        selectedTypes.value.length === props.filters.available_types.length
})

function applyFilters() {
    router.get(
        '/analytics',
        { types: selectedTypes.value },
        {
            preserveScroll: true,
            replace: true,
        },
    )
}

function toggleType(type) {
    if (selectedTypes.value.includes(type)) {
        selectedTypes.value = selectedTypes.value.filter(t => t !== type)
    } else {
        selectedTypes.value = [...selectedTypes.value, type]
    }
    applyFilters()
}

function selectAllTypes() {
    selectedTypes.value = [...props.filters.available_types]
    applyFilters()
}

function clearTypes() {
    selectedTypes.value = []
    applyFilters()
}

const weeklyChartData = computed(() => ({
    labels: props.weekly.map(w => w.label),
    datasets: [
        {
            label: 'Distance [km]',
            data: props.weekly.map(w => w.distance_km),
            tension: 0.3,
            borderColor: '#3b82f6',
            backgroundColor: '#3b82f6',
            borderWidth: 2.5,
            pointRadius: 3,
            pointHoverRadius: 4,
        },
        {
            label: 'Calories',
            data: props.weekly.map(w => w.calories),
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
    labels: props.monthly.map(m => m.label),
    datasets: [
        {
            label: 'Distance [km]',
            data: props.monthly.map(m => m.distance_km),
            tension: 0.3,
            borderColor: '#3b82f6',
            backgroundColor: '#3b82f6',
            borderWidth: 2.5,
            pointRadius: 3,
            pointHoverRadius: 4,
        },
        {
            label: 'Calories',
            data: props.monthly.map(m => m.calories),
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
            ticks: {
                color: axisColor,
            },
            grid: {
                color: gridColor,
            },
        },
        y: {
            beginAtZero: true,
            ticks: {
                color: axisColor,
            },
            grid: {
                color: gridColor,
            },
        },
        y1: {
            beginAtZero: true,
            position: 'right',
            ticks: {
                color: axisColor,
            },
            grid: {
                drawOnChartArea: false,
                color: gridColor,
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

                <div
                    v-if="props.filters.available_types && props.filters.available_types.length"
                    class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-3"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            Activity types included in stats
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-full border px-3 py-1 font-medium transition"
                                :class="allTypesSelected
                                    ? 'border-gray-300 text-gray-700 dark:border-gray-500 dark:text-gray-200'
                                    : 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-300'"
                                @click="allTypesSelected ? clearTypes() : selectAllTypes()"
                            >
                                {{ allTypesSelected ? 'Deselect all' : 'Select all' }}
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="type in props.filters.available_types"
                            :key="type"
                            type="button"
                            @click="toggleType(type)"
                            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition"
                            :class="selectedTypes.includes(type)
                                ? 'bg-blue-600 text-white border-blue-600 dark:bg-blue-500 dark:border-blue-500'
                                : 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700'"
                        >
                            {{ type }}
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

                <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 h-96">
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
