<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({ workout: { type:Object, required:true } })
const totalSets = () => (props.workout.exercises || []).reduce((acc, exercise) => acc + (exercise.sets?.length || 0), 0)
const totalReps = () => (props.workout.exercises || []).reduce((acc, exercise) => acc + exercise.sets.reduce((inner, set) => inner + (set.reps || 0), 0), 0)
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Workout #${props.workout.id}`" />
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 space-y-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Workout details</h1>
                    <Link href="/workouts" class="text-blue-600 hover:underline">Back</Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Date</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ props.workout.date }}</div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Type</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ props.workout.type }}</div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Time</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ props.workout.duration }}</div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Distance [km]</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ Number(props.workout.distance_km).toFixed(2) }}</div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Calories</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ props.workout.calories }}</div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-600 dark:text-white">Notes</div>
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap" v-text="props.workout.notes || '—'"></div>
                </div>

                <div v-if="props.workout.type === 'Strength' && props.workout.exercises?.length" class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">Exercises</div>
                        <div class="text-sm text-gray-600 dark:text-white">Total sets: {{ totalSets() }} • Total reps: {{ totalReps() }}</div>
                    </div>

                    <div v-for="exercise in props.workout.exercises" :key="exercise.id" class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="flex items-center justify-between">
                            <div class="font-medium text-gray-900 dark:text-white">{{ exercise.order_no }}. {{ exercise.name }}</div>
                            <div class="text-sm text-gray-600 dark:text-white" v-if="exercise.catalog_name">Catalog: {{ exercise.catalog_name }}</div>
                        </div>
                        <div class="overflow-x-auto mt-2">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-white">
                                <tr>
                                    <th class="px-3 py-2 text-left">Set</th>
                                    <th class="px-3 py-2 text-left">Reps</th>
                                    <th class="px-3 py-2 text-left">Weight [kg]</th>
                                    <th class="px-3 py-2 text-left">RIR</th>
                                    <th class="px-3 py-2 text-left">Rest [s]</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="set in exercise.sets" :key="set.set_no" class="border-t border-gray-100 dark:border-gray-700">
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.set_no }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.reps ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.weight_kg ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.rir ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.rest_seconds ?? '—' }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
