<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
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
    exercise: { type: Object, required: true },
    series: { type: Array, required: true },
})

const labels = computed(() => props.series.map(p => p.date))
const volumeData = computed(() => props.series.map(p => p.volume))
const oneRmData = computed(() => props.series.map(p => p.est_one_rm_kg))

const chartData = computed(() => ({
    labels: labels.value,
    datasets: [
        {
            label: 'Volume (kg × reps)',
            data: volumeData.value,
            yAxisID: 'y',
            tension: 0.3,
            borderWidth: 2,
            borderColor: 'rgba(59, 130, 246, 1)',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            pointRadius: 3,
            pointBackgroundColor: 'rgba(59, 130, 246, 1)',
        },
        {
            label: 'Best est. 1RM [kg]',
            data: oneRmData.value,
            yAxisID: 'y1',
            tension: 0.3,
            borderWidth: 2,
            borderColor: 'rgba(16, 185, 129, 1)',
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            pointRadius: 3,
            pointBackgroundColor: 'rgba(16, 185, 129, 1)',
        },
    ],
}))

const axisColor = 'rgba(156, 163, 175, 1)'
const gridColor = 'rgba(156, 163, 175, 0.25)'
const tooltipColor = 'rgba(229, 231, 235, 1)'

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
                boxWidth: 8,
            },
        },
        tooltip: {
            titleColor: tooltipColor,
            bodyColor: tooltipColor,
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            borderColor: 'rgba(55, 65, 81, 1)',
            borderWidth: 1,
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
        <Head :title="`Exercise: ${exercise.name}`" />
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 space-y-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ exercise.name }}
                    </h1>
                </div>

                <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 h-80">
                    <Line :data="chartData" :options="chartOptions" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
