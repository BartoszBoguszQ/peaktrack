<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    workout: { type: Object, required: true }
})

const form = useForm({
    date: props.workout.attributes.date,
    type: props.workout.attributes.type,
    duration_seconds: props.workout.attributes.duration_seconds ?? 0,
    distance_km: props.workout.attributes.distance_km ?? 0,
    calories: props.workout.attributes.calories ?? 0,
    notes: props.workout.attributes.notes ?? '',
    exercises: (props.workout.attributes?.exercises ?? props.workout.exercises ?? []).map((exercise) => ({
        exercise_id: exercise.exercise_id ?? null,
        source: exercise.source ?? 'manual',
        external_id: exercise.external_id ?? null,
        name: exercise.name ?? '',
        order_no: exercise.order_no ?? 1,
        catalog_name: exercise.catalog_name ?? null,
        sets: Array.isArray(exercise.sets) && exercise.sets.length
            ? exercise.sets.map((set, index) => ({
                set_no: set.set_no ?? index + 1,
                reps: set.reps ?? null,
                weight_kg: set.weight_kg ?? null,
                rir: set.rir ?? null,
                rest_seconds: set.rest_seconds ?? null
            }))
            : [{ set_no: 1, reps: null, weight_kg: null, rir: null, rest_seconds: null }]
    }))
})

const isStrength = computed(() => form.type === 'Strength')

const hasErrors = computed(() => Object.keys(form.errors || {}).length > 0)
const errorEntries = computed(() => Object.entries(form.errors || {}))

function fieldError(key) {
    return (form.errors && form.errors[key]) ? form.errors[key] : null
}

const durationHms = ref('00:00:00')
const durationHours = ref(0)
const durationMinutes = ref(0)
const durationSecondsPart = ref(0)
const syncingDuration = ref(false)

function pad2(value) {
    return String(Math.max(0, Number.isFinite(value) ? value : 0)).padStart(2, '0').slice(-2)
}

function clampInt(value, min, max) {
    const parsed = Number.parseInt(String(value ?? ''), 10)
    const normalized = Number.isFinite(parsed) ? parsed : 0
    return Math.min(max, Math.max(min, normalized))
}

function toSeconds(hms) {
    const parts = String(hms || '00:00:00').split(':').map((p) => Number.parseInt(p, 10) || 0)
    const hh = parts[0] ?? 0
    const mm = parts[1] ?? 0
    const ss = parts[2] ?? 0

    return hh * 3600 + mm * 60 + ss
}

function secondsToHms(totalSeconds) {
    const total = Math.max(0, Number.parseInt(totalSeconds ?? 0, 10) || 0)
    const hh = Math.floor(total / 3600)
    const mm = Math.floor((total % 3600) / 60)
    const ss = total % 60

    return `${pad2(hh)}:${pad2(mm)}:${pad2(ss)}`
}

function syncDurationFromParts() {
    syncingDuration.value = true

    const hh = clampInt(durationHours.value, 0, 999)
    const mm = clampInt(durationMinutes.value, 0, 59)
    const ss = clampInt(durationSecondsPart.value, 0, 59)

    durationHours.value = hh
    durationMinutes.value = mm
    durationSecondsPart.value = ss

    durationHms.value = `${pad2(hh)}:${pad2(mm)}:${pad2(ss)}`
    form.duration_seconds = hh * 3600 + mm * 60 + ss

    syncingDuration.value = false
}

function syncPartsFromFormDuration() {
    syncingDuration.value = true

    const totalSeconds = Math.max(0, Number.parseInt(form.duration_seconds ?? 0, 10) || 0)
    const hh = Math.floor(totalSeconds / 3600)
    const mm = Math.floor((totalSeconds % 3600) / 60)
    const ss = totalSeconds % 60

    durationHours.value = hh
    durationMinutes.value = mm
    durationSecondsPart.value = ss
    durationHms.value = secondsToHms(totalSeconds)

    syncingDuration.value = false
}

watch([durationHours, durationMinutes, durationSecondsPart], () => {
    if (syncingDuration.value) return
    syncDurationFromParts()
})

watch(() => form.duration_seconds, () => {
    if (syncingDuration.value) return
    syncPartsFromFormDuration()
})

watch(durationHms, (value) => {
    if (syncingDuration.value) return

    syncingDuration.value = true
    form.duration_seconds = toSeconds(value)
    syncingDuration.value = false

    syncPartsFromFormDuration()
})

watch(isStrength, (value) => {
    if (value) {
        form.distance_km = 0
    }
})

syncPartsFromFormDuration()

const exerciseSearchQuery = ref('')
const exerciseSearchResults = ref([])
const exerciseSearchLoading = ref(false)
const exerciseSearchError = ref('')

function addExercise() {
    form.exercises.push({
        exercise_id: null,
        source: 'manual',
        external_id: null,
        name: '',
        order_no: form.exercises.length + 1,
        catalog_name: null,
        sets: [{ set_no: 1, reps: null, weight_kg: null, rir: null, rest_seconds: null }]
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
    exercise.sets.push({ set_no: nextNo, reps: null, weight_kg: null, rir: null, rest_seconds: null })
}

function removeSet(exerciseIndex, setIndex) {
    const exercise = form.exercises[exerciseIndex]
    if (!exercise || !exercise.sets) return

    exercise.sets.splice(setIndex, 1)
    exercise.sets.forEach((s, i) => {
        s.set_no = i + 1
    })
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
            params: { query: exerciseSearchQuery.value }
        })

        const raw = response.data
        const items = Array.isArray(raw?.data) ? raw.data : Array.isArray(raw) ? raw : []

        exerciseSearchResults.value = items.map((item) => {
            const attributes = item?.attributes ?? {}

            return {
                id: item.id ?? null,
                name: attributes.name ?? item.name ?? '',
                muscle_group: attributes.muscle_group ?? item.muscle_group ?? null,
                lookup_source: attributes.lookup_source ?? item.lookup_source ?? null,
                source: attributes.source ?? item.source ?? null,
                external_id: attributes.external_id ?? item.external_id ?? null
            }
        })
    } catch (e) {
        exerciseSearchError.value = 'Search failed'
    } finally {
        exerciseSearchLoading.value = false
    }
}

function pickExercise(exerciseIndex, picked) {
    const exercise = form.exercises[exerciseIndex]
    if (!exercise || !picked) return

    const attributes = picked?.attributes ?? {}
    const lookupSource = attributes.lookup_source ?? picked.lookup_source ?? null

    const pickedId = picked.id ?? null
    const name = attributes.name ?? picked.name ?? ''
    const externalId = attributes.external_id ?? picked.external_id ?? null

    if (lookupSource === 'local' && pickedId !== null && /^\d+$/.test(String(pickedId))) {
        exercise.exercise_id = Number(pickedId)
        exercise.source = 'manual'
        exercise.external_id = null
    } else {
        exercise.exercise_id = null
        exercise.source = 'api'
        exercise.external_id = externalId !== null ? String(externalId) : (pickedId !== null ? String(pickedId) : null)
    }

    exercise.name = name || exercise.name
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
                ? form.exercises.map((exerciseItem, exerciseIndex) => {
                    const exerciseId = exerciseItem.exercise_id ?? null
                    const name = String(exerciseItem.name || '').trim()

                    if (exerciseId) {
                        return {
                            exercise_id: exerciseId,
                            source: 'manual',
                            external_id: null,
                            name,
                            order_no: exerciseIndex + 1,
                            sets: (exerciseItem.sets || []).map((setItem, setIndex) => ({
                                set_no: setItem.set_no ?? setIndex + 1,
                                reps: setItem.reps,
                                weight_kg: setItem.weight_kg,
                                rir: setItem.rir,
                                rest_seconds: setItem.rest_seconds
                            }))
                        }
                    }

                    const externalId = exerciseItem.external_id !== null && String(exerciseItem.external_id).trim() !== ''
                        ? String(exerciseItem.external_id).trim()
                        : null

                    return {
                        exercise_id: null,
                        source: externalId ? 'api' : 'manual',
                        external_id: externalId,
                        name,
                        order_no: exerciseIndex + 1,
                        sets: (exerciseItem.sets || []).map((setItem, setIndex) => ({
                            set_no: setItem.set_no ?? setIndex + 1,
                            reps: setItem.reps,
                            weight_kg: setItem.weight_kg,
                            rir: setItem.rir,
                            rest_seconds: setItem.rest_seconds
                        }))
                    }
                })
                : []
    }

    form.transform(() => payload)
        .put(route('workouts.update', props.workout.id), {
            preserveScroll: true,
            onFinish: () => form.transform(data => data)
        })
}
</script>

<template>
    <Head title="Edit workout"/>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit workout</h2>
                <Link
                    :href="route('workouts.index')"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Back
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div v-if="hasErrors" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                        <div class="font-semibold">Fix the errors below:</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li v-for="([key, message], idx) in errorEntries" :key="key" v-show="idx < 8">
                                {{ message }}
                            </li>
                        </ul>
                        <div v-if="errorEntries.length > 8" class="mt-2 text-xs opacity-80">
                            And {{ errorEntries.length - 8 }} more.
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Date
                                </label>
                                <input
                                    v-model="form.date"
                                    type="date"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                                <div v-if="fieldError('date')" class="mt-1 text-sm text-red-600">{{ fieldError('date') }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Type
                                </label>
                                <select
                                    v-model="form.type"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="Run">Run</option>
                                    <option value="Ride">Ride</option>
                                    <option value="Swim">Swim</option>
                                    <option value="Strength">Strength</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div v-if="fieldError('type')" class="mt-1 text-sm text-red-600">{{ fieldError('type') }}</div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Duration
                                    </label>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 tabular-nums">
                                        {{ durationHms }}
                                    </div>
                                </div>

                                <div class="mt-1 flex gap-2">
                                    <input
                                        v-model.number="durationHours"
                                        type="number"
                                        min="0"
                                        max="999"
                                        inputmode="numeric"
                                        placeholder="HH"
                                        class="h-11 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-center tabular-nums"
                                    />
                                    <input
                                        v-model.number="durationMinutes"
                                        type="number"
                                        min="0"
                                        max="59"
                                        inputmode="numeric"
                                        placeholder="MM"
                                        class="h-11 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-center tabular-nums"
                                    />
                                    <input
                                        v-model.number="durationSecondsPart"
                                        type="number"
                                        min="0"
                                        max="59"
                                        inputmode="numeric"
                                        placeholder="SS"
                                        class="h-11 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-center tabular-nums"
                                    />
                                </div>

                                <div v-if="fieldError('duration_seconds')" class="mt-1 text-sm text-red-600">{{ fieldError('duration_seconds') }}</div>
                            </div>

                            <div v-if="!isStrength">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Distance [km]
                                </label>
                                <input
                                    v-model.number="form.distance_km"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    inputmode="decimal"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                                <div v-if="fieldError('distance_km')" class="mt-1 text-sm text-red-600">{{ fieldError('distance_km') }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Calories
                                </label>
                                <input
                                    v-model.number="form.calories"
                                    type="number"
                                    min="0"
                                    step="1"
                                    inputmode="numeric"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                                <div v-if="fieldError('calories')" class="mt-1 text-sm text-red-600">{{ fieldError('calories') }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                Notes
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                            <div v-if="fieldError('notes')" class="mt-1 text-sm text-red-600">{{ fieldError('notes') }}</div>
                        </div>
                    </div>

                    <div v-if="isStrength" class="rounded-2xl bg-white dark:bg-gray-800 p-6 space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">Exercises</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Edit exercises and sets for your strength workout.</div>
                            </div>
                            <button
                                type="button"
                                @click="addExercise"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Add exercise
                            </button>
                        </div>

                        <div v-if="fieldError('exercises')" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                            {{ fieldError('exercises') }}
                        </div>

                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Search exercise
                                </label>
                            </div>

                            <div class="flex gap-2">
                                <input
                                    v-model="exerciseSearchQuery"
                                    type="text"
                                    placeholder="Type to search..."
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                    @click="searchExercises"
                                >
                                    Search
                                </button>
                            </div>

                            <div v-if="exerciseSearchLoading" class="text-sm text-gray-500 dark:text-gray-400">Loading...</div>
                            <div v-if="exerciseSearchError" class="text-sm text-red-600">{{ exerciseSearchError }}</div>

                            <div v-if="exerciseSearchResults.length" class="mt-2 space-y-2">
                                <div
                                    v-for="result in exerciseSearchResults"
                                    :key="`${result.lookup_source || 'x'}-${result.id}`"
                                    class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 p-3"
                                >
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-white truncate">{{ result.name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ result.muscle_group || result.lookup_source || '-' }}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                                        @click="() => { if (!form.exercises.length) addExercise(); pickExercise(form.exercises.length - 1, result) }"
                                    >
                                        Use
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div
                                v-for="(exercise, exerciseIndex) in form.exercises"
                                :key="exerciseIndex"
                                class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ exercise.order_no }}. {{ exercise.name || 'Exercise' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ exercise.source || '-' }}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeExercise(exerciseIndex)"
                                        class="inline-flex items-center justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                                    >
                                        Remove
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                                        <input
                                            v-model="exercise.name"
                                            type="text"
                                            placeholder="Exercise name"
                                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                        />
                                        <div v-if="fieldError(`exercises.${exerciseIndex}.name`)" class="mt-1 text-sm text-red-600">
                                            {{ fieldError(`exercises.${exerciseIndex}.name`) }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Order</label>
                                        <input
                                            v-model.number="exercise.order_no"
                                            type="number"
                                            min="1"
                                            step="1"
                                            inputmode="numeric"
                                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                        />
                                        <div v-if="fieldError(`exercises.${exerciseIndex}.order_no`)" class="mt-1 text-sm text-red-600">
                                            {{ fieldError(`exercises.${exerciseIndex}.order_no`) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">Sets</div>
                                        <button
                                            type="button"
                                            @click="addSet(exerciseIndex)"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                        >
                                            Add set
                                        </button>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-200">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Set</th>
                                                <th class="px-3 py-2 text-left">Reps</th>
                                                <th class="px-3 py-2 text-left">Weight [kg]</th>
                                                <th class="px-3 py-2 text-left">RIR</th>
                                                <th class="px-3 py-2 text-left">Rest [s]</th>
                                                <th class="px-3 py-2 text-left"></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="(setItem, setIndex) in exercise.sets" :key="setIndex" class="border-t border-gray-100 dark:border-gray-700">
                                                <td class="px-3 py-2 text-gray-900 dark:text-white">{{ setItem.set_no }}</td>
                                                <td class="px-3 py-2 align-top">
                                                    <input v-model.number="setItem.reps" type="number" min="0" step="1" inputmode="numeric" class="w-24 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                                    <div v-if="fieldError(`exercises.${exerciseIndex}.sets.${setIndex}.reps`)" class="mt-1 text-xs text-red-600">
                                                        {{ fieldError(`exercises.${exerciseIndex}.sets.${setIndex}.reps`) }}
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 align-top">
                                                    <input v-model.number="setItem.weight_kg" type="number" min="0" step="0.5" inputmode="decimal" class="w-28 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                                    <div v-if="fieldError(`exercises.${exerciseIndex}.sets.${setIndex}.weight_kg`)" class="mt-1 text-xs text-red-600">
                                                        {{ fieldError(`exercises.${exerciseIndex}.sets.${setIndex}.weight_kg`) }}
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 align-top">
                                                    <input v-model.number="setItem.rir" type="number" min="0" step="1" inputmode="numeric" class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                                    <div v-if="fieldError(`exercises.${exerciseIndex}.sets.${setIndex}.rir`)" class="mt-1 text-xs text-red-600">
                                                        {{ fieldError(`exercises.${exerciseIndex}.sets.${setIndex}.rir`) }}
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 align-top">
                                                    <input v-model.number="setItem.rest_seconds" type="number" min="0" step="1" inputmode="numeric" class="w-24 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                                    <div v-if="fieldError(`exercises.${exerciseIndex}.sets.${setIndex}.rest_seconds`)" class="mt-1 text-xs text-red-600">
                                                        {{ fieldError(`exercises.${exerciseIndex}.sets.${setIndex}.rest_seconds`) }}
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 align-top">
                                                    <button type="button" @click="removeSet(exerciseIndex, setIndex)" class="rounded-lg bg-red-600 px-2 py-1 text-xs font-medium text-white hover:bg-red-700">
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div v-if="fieldError(`exercises.${exerciseIndex}.sets`)" class="mt-2 text-sm text-red-600">
                                        {{ fieldError(`exercises.${exerciseIndex}.sets`) }}
                                    </div>

                                    <div v-if="fieldError(`exercises.${exerciseIndex}.exercise_id`)" class="text-sm text-red-600">
                                        {{ fieldError(`exercises.${exerciseIndex}.exercise_id`) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
