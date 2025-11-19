<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    exercises: { type: Array, required: true },
})

const searchQuery = ref('')

const filteredExercises = computed(() => {
    const query = searchQuery.value.trim().toLowerCase()
    if (!query) {
        return props.exercises
    }

    return props.exercises.filter(exerciseRow => {
        const name = String(exerciseRow.name || '').toLowerCase()
        return name.includes(query)
    })
})

function formatNumber(value, digits = 1) {
    if (value === null || value === undefined) {
        return '0'
    }
    return Number(value).toFixed(digits)
}

function formatDate(dateString) {
    if (!dateString) {
        return '—'
    }
    return dateString
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Strength exercises" />
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Strength Analytics
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            All strength exercises you have performed, with best weights and estimated one-rep maxes.
                        </p>
                    </div>
                    <Link
                        href="/records"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Back to records
                    </Link>
                </div>

                <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="font-semibold text-gray-900 dark:text-white">
                            Exercises list
                        </div>
                        <div class="w-full md:w-64">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by exercise name"
                                class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto -mx-3">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-600 dark:text-gray-200">
                                <th class="px-3 py-2">Exercise</th>
                                <th class="px-3 py-2 text-right">Sessions</th>
                                <th class="px-3 py-2 text-right">Last performed</th>
                                <th class="px-3 py-2 text-right">Best weight [kg]</th>
                                <th class="px-3 py-2 text-right">Best est. 1RM [kg]</th>
                                <th class="px-3 py-2 text-right">Total volume</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="exerciseRow in filteredExercises"
                                :key="exerciseRow.representative_workout_exercise_id"
                                class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">
                                    {{ exerciseRow.name }}
                                </td>
                                <td class="px-3 py-2 text-right text-gray-900 dark:text-white">
                                    {{ exerciseRow.sessions_count }}
                                </td>
                                <td class="px-3 py-2 text-right text-gray-900 dark:text-white">
                                    {{ formatDate(exerciseRow.last_date) }}
                                </td>
                                <td class="px-3 py-2 text-right text-gray-900 dark:text-white">
                                    {{ formatNumber(exerciseRow.best_weight_kg, 1) }}
                                </td>
                                <td class="px-3 py-2 text-right text-gray-900 dark:text-white">
                                    {{ formatNumber(exerciseRow.best_one_rm_kg, 1) }}
                                </td>
                                <td class="px-3 py-2 text-right text-gray-900 dark:text-white">
                                    {{ Math.round(exerciseRow.total_volume) }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <Link
                                        :href="route('workouts.exercise.stats', exerciseRow.representative_workout_exercise_id)"
                                        class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                    >
                                        View chart
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!filteredExercises.length">
                                <td colspan="7" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No strength exercises found.
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
