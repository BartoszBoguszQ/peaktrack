<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({ workout: { type: Object, required: true } })

const isStrength = computed(() => props.workout.attributes?.type === 'Strength')

const workoutExercises = computed(() => {
    const attributeExercises = props.workout.attributes?.exercises
    if (Array.isArray(attributeExercises)) {
        return attributeExercises
    }

    const relationshipExercises = props.workout.relationships?.exercises
    if (!Array.isArray(relationshipExercises)) {
        return []
    }

    return relationshipExercises.map((exerciseItem) => {
        const exerciseAttributes = exerciseItem?.attributes ?? {}
        const setsRelationship = exerciseItem?.relationships?.sets
        const setsArray = Array.isArray(setsRelationship) ? setsRelationship : []

        return {
            id: exerciseItem?.id ?? null,
            exercise_id: exerciseAttributes.exercise_id ?? null,
            external_source: exerciseAttributes.external_source ?? null,
            external_id: exerciseAttributes.external_id ?? null,
            name: exerciseAttributes.name ?? '',
            order_no: exerciseAttributes.order_no ?? null,
            catalog_name: null,
            sets: setsArray.map((setItem) => {
                const setAttributes = setItem?.attributes ?? {}
                return {
                    set_no: setAttributes.set_no ?? null,
                    reps: setAttributes.reps ?? null,
                    weight_kg: setAttributes.weight_kg ?? null,
                    rir: setAttributes.rir ?? null,
                    rest_seconds: setAttributes.rest_seconds ?? null
                }
            })
        }
    })
})

const totalExercises = computed(() => workoutExercises.value.length)

const totalSets = computed(() =>
    workoutExercises.value.reduce((acc, exercise) => acc + ((exercise.sets || []).length), 0)
)

const totalReps = computed(() =>
    workoutExercises.value.reduce(
        (acc, exercise) => acc + (exercise.sets || []).reduce((inner, set) => inner + (Number(set.reps) || 0), 0),
        0
    )
)

const totalVolumeKg = computed(() =>
    workoutExercises.value.reduce((acc, exercise) => {
        return acc + (exercise.sets || []).reduce((inner, set) => {
            const reps = Number(set.reps) || 0
            const weight = Number(set.weight_kg) || 0
            return inner + reps * weight
        }, 0)
    }, 0)
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
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ props.workout.attributes.date }}</div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Type</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ props.workout.attributes.type }}</div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Time</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ props.workout.attributes.duration }}</div>
                    </div>

                    <div v-if="!isStrength" class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Distance [km]</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ Number(props.workout.attributes.distance_km).toFixed(2) }}</div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Calories</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ props.workout.attributes.calories }}</div>
                    </div>

                    <div v-if="isStrength" class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Exercises</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ totalExercises }}</div>
                    </div>

                    <div v-if="isStrength" class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Sets / Reps</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ totalSets }} / {{ totalReps }}</div>
                    </div>

                    <div v-if="isStrength" class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Volume [kg]</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ totalVolumeKg.toFixed(1) }}</div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-600 dark:text-white">Notes</div>
                    <div class="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap" v-text="props.workout.attributes.notes || '-'"></div>
                </div>

                <div v-if="isStrength && workoutExercises.length" class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">Exercises</div>
                        <div class="text-sm text-gray-600 dark:text-white">
                            Sets: {{ totalSets }} • Reps: {{ totalReps }} • Volume: {{ totalVolumeKg.toFixed(1) }} kg
                        </div>
                    </div>

                    <div v-for="exercise in workoutExercises" :key="exercise.id" class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="flex items-center justify-between">
                            <div class="font-medium text-gray-900 dark:text-white">
                                <Link
                                    v-if="exercise.id"
                                    :href="route('workouts.exercise.stats', exercise.id)"
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ exercise.order_no }}. {{ exercise.name }}
                                </Link>
                                <span v-else>
                                    {{ exercise.order_no }}. {{ exercise.name }}
                                </span>
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
