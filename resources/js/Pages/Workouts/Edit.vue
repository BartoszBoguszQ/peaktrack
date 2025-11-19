<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    workout: { type: Object, required: true }
})

const form = useForm({
    date: props.workout.date,
    type: props.workout.type,
    duration_seconds: props.workout.duration_seconds ?? 0,
    distance_km: props.workout.distance_km ?? 0,
    calories: props.workout.calories ?? 0,
    notes: props.workout.notes ?? '',
    exercises: props.workout.exercises ?? []
})

const isStrength = computed(() => form.type === 'Strength')

const durationHms = ref('00:00:00')

function toSeconds(hms) {
    const [h, m, s] = (hms || '0:0:0').split(':').map(v => parseInt(v || '0', 10))
    return h * 3600 + m * 60 + s
}

function fromSeconds(seconds) {
    if (!seconds || seconds < 0) return '00:00:00'
    const h = Math.floor(seconds / 3600)
    const m = Math.floor((seconds % 3600) / 60)
    const s = seconds % 60
    return [h, m, s].map(v => String(v).padStart(2, '0')).join(':')
}

durationHms.value = fromSeconds(form.duration_seconds || 0)

const exerciseSearchQuery = ref('')
const exerciseSearchResults = ref([])
const exerciseSearchLoading = ref(false)
const exerciseSearchError = ref('')

function addExercise() {
    form.exercises.push({
        exercise_id: null,
        external_source: null,
        external_id: null,
        name: '',
        order_no: form.exercises.length + 1,
        catalog_name: null,
        sets: [
            {
                set_no: 1,
                reps: null,
                weight_kg: null,
                rir: null,
                rest_seconds: null
            }
        ]
    })
}

function removeExercise(index) {
    form.exercises.splice(index, 1)
    form.exercises.forEach((ex, i) => {
        ex.order_no = i + 1
    })
}

function addSet(exerciseIndex) {
    const exercise = form.exercises[exerciseIndex]
    if (!exercise) return
    const nextNo = (exercise.sets?.length || 0) + 1
    exercise.sets = exercise.sets || []
    exercise.sets.push({
        set_no: nextNo,
        reps: null,
        weight_kg: null,
        rir: null,
        rest_seconds: null
    })
}

function removeSet(exerciseIndex, setIndex) {
    const exercise = form.exercises[exerciseIndex]
    if (!exercise || !exercise.sets) return
    if (exercise.sets.length === 1) {
        exercise.sets[0].reps = null
        exercise.sets[0].weight_kg = null
        exercise.sets[0].rir = null
        exercise.sets[0].rest_seconds = null
        return
    }
    exercise.sets.splice(setIndex, 1)
    exercise.sets.forEach((set, i) => {
        set.set_no = i + 1
    })
}

function quickSets(exerciseIndex, count) {
    const exercise = form.exercises[exerciseIndex]
    if (!exercise) return
    const base = exercise.sets?.[exercise.sets.length - 1] || {
        reps: null,
        weight_kg: null,
        rir: null,
        rest_seconds: null
    }
    exercise.sets = Array.from({ length: count }, (_, i) => ({
        set_no: i + 1,
        reps: base.reps,
        weight_kg: base.weight_kg,
        rir: base.rir,
        rest_seconds: base.rest_seconds
    }))
}

async function searchExercises() {
    exerciseSearchError.value = ''
    exerciseSearchResults.value = []
    if (!exerciseSearchQuery.value || exerciseSearchQuery.value.length < 2) {
        return
    }
    exerciseSearchLoading.value = true
    try {
        const response = await axios.get('/api/exercises/search', {
            params: { q: exerciseSearchQuery.value }
        })
        const data = response.data
        exerciseSearchResults.value = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
    } catch (e) {
        exerciseSearchError.value = 'Search failed'
    } finally {
        exerciseSearchLoading.value = false
    }
}

function pickExercise(exerciseIndex, picked) {
    const exercise = form.exercises[exerciseIndex]
    if (!exercise || !picked) return
    exercise.exercise_id = picked.id ?? exercise.exercise_id ?? null
    exercise.external_source = picked.external_source ?? exercise.external_source ?? null
    exercise.external_id = picked.external_id ?? exercise.external_id ?? null
    exercise.name = picked.name || picked.title || exercise.name
    exercise.catalog_name = picked.muscle_group || picked.category || exercise.catalog_name
}

function clearPicked(exerciseIndex) {
    const exercise = form.exercises[exerciseIndex]
    if (!exercise) return
    exercise.exercise_id = null
    exercise.external_source = null
    exercise.external_id = null
    exercise.catalog_name = null
}

function submit() {
    form.duration_seconds = toSeconds(durationHms.value)
    const normalizedDistanceKm = isStrength.value ? 0 : form.distance_km

    const payload = {
        date: form.date,
        type: form.type,
        duration_seconds: form.duration_seconds,
        distance_km: normalizedDistanceKm,
        calories: form.calories,
        notes: form.notes,
        exercises:
            form.type === 'Strength'
                ? form.exercises.map((exerciseItem, exerciseIndex) => ({
                    exercise_id: exerciseItem.exercise_id,
                    external_source: exerciseItem.external_source,
                    external_id: exerciseItem.external_id,
                    name: exerciseItem.name,
                    order_no: exerciseIndex + 1,
                    sets: (exerciseItem.sets || []).map((setItem, setIndex) => ({
                        set_no: setItem.set_no ?? setIndex + 1,
                        reps: setItem.reps,
                        weight_kg: setItem.weight_kg,
                        rir: setItem.rir,
                        rest_seconds: setItem.rest_seconds
                    }))
                }))
                : []
    }

    form.transform(() => payload).put(`/workouts/${props.workout.id}`)
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit workout" />
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Edit workout
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Update details of your training session.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            :href="route('workouts.show', props.workout.id)"
                            class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Back
                        </Link>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Date</label>
                                <input
                                    v-model="form.date"
                                    type="date"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Type</label>
                                <select
                                    v-model="form.type"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="Run">Run</option>
                                    <option value="Strength">Strength</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Time (hh:mm:ss)</label>
                                <input
                                    v-model="durationHms"
                                    type="text"
                                    placeholder="00:30:00"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div v-if="!isStrength">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Distance [km]</label>
                                <input
                                    v-model.number="form.distance_km"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Calories</label>
                                <input
                                    v-model.number="form.calories"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div
                        v-if="isStrength"
                        class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                Exercises
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                @click="addExercise"
                            >
                                Add exercise
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <input
                                v-model="exerciseSearchQuery"
                                type="text"
                                placeholder="Search in catalog…"
                                class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white md:w-64"
                            />
                            <button
                                type="button"
                                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                @click="searchExercises"
                            >
                                Search
                            </button>
                            <span
                                v-if="exerciseSearchLoading"
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                Searching…
                            </span>
                            <span
                                v-if="exerciseSearchError"
                                class="text-xs text-red-500"
                            >
                                {{ exerciseSearchError }}
                            </span>
                        </div>

                        <div
                            v-if="exerciseSearchResults.length"
                            class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-700 dark:bg-gray-900"
                        >
                            <div class="mb-1 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                Results
                            </div>
                            <div class="grid gap-1 md:grid-cols-2">
                                <button
                                    v-for="result in exerciseSearchResults"
                                    :key="result.id || result.external_id || result.name"
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-lg bg-white px-3 py-2 text-left hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700"
                                    @click="pickExercise(form.exercises.length - 1, result)"
                                >
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ result.name || result.title }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ result.muscle_group || result.category || 'Exercise' }}
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div
                            v-for="(exercise, exerciseIndex) in form.exercises"
                            :key="exerciseIndex"
                            class="rounded-xl border border-gray-200 p-3 dark:border-gray-700"
                        >
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">
                                        Name
                                    </label>
                                    <input
                                        v-model="exercise.name"
                                        type="text"
                                        class="mt-1 block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    />
                                    <div
                                        v-if="exercise.catalog_name"
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Catalog: {{ exercise.catalog_name }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 md:self-start">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                        @click="clearPicked(exerciseIndex)"
                                    >
                                        Clear link
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-lg bg-red-600 px-2 py-1 text-xs font-medium text-white hover:bg-red-700"
                                        @click="removeExercise(exerciseIndex)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Quick sets:
                                </span>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                    @click="quickSets(exerciseIndex, 3)"
                                >
                                    3x
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                    @click="quickSets(exerciseIndex, 4)"
                                >
                                    4x
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                    @click="quickSets(exerciseIndex, 5)"
                                >
                                    5x
                                </button>
                            </div>

                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-xs md:text-sm">
                                    <thead class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-200">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Set</th>
                                        <th class="px-3 py-2 text-left">Reps</th>
                                        <th class="px-3 py-2 text-left">Weight [kg]</th>
                                        <th class="px-3 py-2 text-left">RIR</th>
                                        <th class="px-3 py-2 text-left">Rest [s]</th>
                                        <th class="px-3 py-2 text-right"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr
                                        v-for="(set, setIndex) in exercise.sets"
                                        :key="setIndex"
                                        class="border-t border-gray-100 dark:border-gray-700"
                                    >
                                        <td class="px-3 py-2 text-gray-900 dark:text-white">
                                            {{ set.set_no }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model.number="set.reps"
                                                type="number"
                                                min="0"
                                                class="block w-20 rounded-lg border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model.number="set.weight_kg"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                class="block w-24 rounded-lg border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model.number="set.rir"
                                                type="number"
                                                step="1"
                                                min="-5"
                                                max="5"
                                                class="block w-16 rounded-lg border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model.number="set.rest_seconds"
                                                type="number"
                                                min="0"
                                                class="block w-20 rounded-lg border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                            />
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <button
                                                type="button"
                                                class="rounded-lg bg-red-50 px-2 py-1 text-xs text-red-600 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-200 dark:hover:bg-red-900/70"
                                                @click="removeSet(exerciseIndex, setIndex)"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-dashed border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                    @click="addSet(exerciseIndex)"
                                >
                                    Add set
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            :disabled="form.processing"
                        >
                            Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
