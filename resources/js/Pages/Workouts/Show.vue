<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({ workout: { type:Object, required:true } })
const totalSets = () =>
    (props.workout.exercises || []).reduce(
        (acc, exercise) => acc + ((exercise.sets || []).length),
        0
    )

const totalReps = () =>
    (props.workout.exercises || []).reduce(
        (acc, exercise) =>
            acc + (exercise.sets || []).reduce(
                (inner, set) => inner + (set.reps || 0),
                0
            ),
        0
    )
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Workout #${props.workout.id}`" />
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Workout details
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Overview of this training session.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            href="/workouts"
                            class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Back
                        </Link>
                        <Link
                            :href="route('workouts.edit', props.workout.id)"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            Edit
                        </Link>
                        <Link
                            :href="route('workouts.destroy', props.workout.id)"
                            method="delete"
                            as="button"
                            class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                        >
                            Delete
                        </Link>
                    </div>
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
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap" v-text="props.workout.notes || '-'"></div>
                </div>

                <div v-if="props.workout.type === 'Strength' && props.workout.exercises?.length" class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">Exercises</div>
                        <div class="text-sm text-gray-600 dark:text-white">Total sets: {{ totalSets() }} • Total reps: {{ totalReps() }}</div>
                    </div>

                    <div v-for="exercise in props.workout.exercises" :key="exercise.id" class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="flex items-center justify-between">
                            <div class="font-medium text-gray-900 dark:text-white">
                                <Link
                                    :href="route('workouts.exercise.stats', exercise.id)"
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ exercise.order_no }}. {{ exercise.name }}
                                </Link>
                            </div>
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
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.reps ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.weight_kg ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.rir ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ set.rest_seconds ?? '-' }}</td>
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
