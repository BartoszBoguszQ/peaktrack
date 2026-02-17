<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref, computed, watch, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'

const form = useForm({
    date: new Date().toISOString().slice(0, 10),
    type: 'Run',
    duration_seconds: 0,
    distance_km: 0,
    calories: 0,
    notes: '',
    exercises: []
})

const isStrength = computed(() => form.type === 'Strength')

const durationHms = ref('00:00:00')
const durationHours = ref(0)
const durationMinutes = ref(0)
const durationSecondsPart = ref(0)

function pad2(value) {
    return String(Math.max(0, Number.isFinite(value) ? value : 0)).padStart(2, '0').slice(-2)
}

function clampInt(value, min, max) {
    const parsed = Number.parseInt(String(value ?? ''), 10)
    const normalized = Number.isFinite(parsed) ? parsed : 0
    return Math.min(max, Math.max(min, normalized))
}

function fromSeconds(seconds) {
    const safeSeconds = Math.max(0, Number.parseInt(String(seconds ?? 0), 10) || 0)
    const h = Math.floor(safeSeconds / 3600)
    const m = Math.floor((safeSeconds % 3600) / 60)
    const s = safeSeconds % 60
    return [h, m, s].map(v => String(v).padStart(2, '0')).join(':')
}

function toSeconds(hms) {
    const match = String(hms ?? '').match(/^(\d{2,}):(\d{2}):(\d{2})$/)
    if (!match) return 0
    const h = clampInt(match[1], 0, 999)
    const m = clampInt(match[2], 0, 59)
    const s = clampInt(match[3], 0, 59)
    return h * 3600 + m * 60 + s
}

function loadDurationPartsFromSeconds(seconds) {
    const safeSeconds = Math.max(0, Number.parseInt(String(seconds ?? 0), 10) || 0)
    durationHours.value = Math.floor(safeSeconds / 3600)
    durationMinutes.value = Math.floor((safeSeconds % 3600) / 60)
    durationSecondsPart.value = safeSeconds % 60
    durationHms.value = fromSeconds(safeSeconds)
}

function syncDurationFromParts() {
    durationHours.value = clampInt(durationHours.value, 0, 999)
    durationMinutes.value = clampInt(durationMinutes.value, 0, 59)
    durationSecondsPart.value = clampInt(durationSecondsPart.value, 0, 59)

    durationHms.value = `${pad2(durationHours.value)}:${pad2(durationMinutes.value)}:${pad2(durationSecondsPart.value)}`
    form.duration_seconds = toSeconds(durationHms.value)
}

loadDurationPartsFromSeconds(form.duration_seconds || 0)

watch([durationHours, durationMinutes, durationSecondsPart], () => {
    syncDurationFromParts()
})

function newUid() {
    return `${Date.now()}-${Math.random().toString(16).slice(2)}`
}

function makeEmptyExercise(orderNo, initialName = '') {
    return {
        uid: newUid(),
        exercise_id: null,
        external_source: null,
        external_id: null,
        picked_name: initialName || '',
        display_name: initialName || '',
        order_no: orderNo,
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
    }
}

const exerciseNameRefs = ref({})

function setExerciseNameRef(uid) {
    return (el) => {
        if (el) {
            exerciseNameRefs.value[uid] = el
        }
    }
}

const exerciseSearchQuery = ref('')
const exerciseSearchResults = ref([])
const exerciseSearchLoading = ref(false)
const exerciseSearchError = ref('')

const isSearchOpen = ref(false)
const searchBoxRef = ref(null)

let searchDebounceTimer = null

function closeSearch() {
    isSearchOpen.value = false
    exerciseSearchResults.value = []
    exerciseSearchError.value = ''
}

function onSearchKeydown(e) {
    if (e.key === 'Escape') {
        closeSearch()
    }
}

function onDocumentClick(e) {
    const el = searchBoxRef.value
    if (!el) return
    if (!el.contains(e.target)) {
        closeSearch()
    }
}

window.addEventListener('click', onDocumentClick)

onBeforeUnmount(() => {
    window.removeEventListener('click', onDocumentClick)
    clearTimeout(searchDebounceTimer)
})

watch(exerciseSearchQuery, (value) => {
    const query = String(value || '').trim()
    clearTimeout(searchDebounceTimer)

    if (query.length < 2) {
        closeSearch()
        return
    }

    isSearchOpen.value = true
    searchDebounceTimer = setTimeout(() => {
        searchExercises()
    }, 300)
})

async function focusExerciseName(uid) {
    await nextTick()
    const el = exerciseNameRefs.value[uid]
    if (el && typeof el.focus === 'function') {
        el.focus()
        if (typeof el.select === 'function') el.select()
    }
}

function addExercise() {
    const newExercise = makeEmptyExercise(form.exercises.length + 1, '')
    form.exercises.push(newExercise)
    focusExerciseName(newExercise.uid)
}

function removeExercise(index) {
    const removed = form.exercises[index]
    if (removed?.uid && exerciseNameRefs.value[removed.uid]) {
        delete exerciseNameRefs.value[removed.uid]
    }

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
    if (!exercise?.sets) return
    exercise.sets.splice(setIndex, 1)
    exercise.sets.forEach((setItem, i) => {
        setItem.set_no = i + 1
    })
}

async function searchExercises() {
    exerciseSearchError.value = ''
    exerciseSearchResults.value = []

    const query = String(exerciseSearchQuery.value || '').trim()
    if (query.length < 2) {
        closeSearch()
        return
    }

    isSearchOpen.value = true
    exerciseSearchLoading.value = true

    try {
        const response = await axios.get('/api/exercises/search', {
            params: { query }
        })

        const raw = response.data
        const items = Array.isArray(raw?.data) ? raw.data : Array.isArray(raw) ? raw : []

        exerciseSearchResults.value = items.map((item) => {
            const attributes = item?.attributes ?? null
            if (!attributes) return item

            return {
                id: item.id ?? null,
                name: attributes.name ?? '',
                muscle_group: attributes.muscle_group ?? null,
                external_source: attributes.external_source ?? null,
                external_id: attributes.external_id ?? null,
                source: attributes.source ?? item.source ?? null
            }
        })
    } catch (e) {
        exerciseSearchError.value = 'Search failed'
    } finally {
        exerciseSearchLoading.value = false
    }
}

function applyPickedToExercise(exercise, picked) {
    const source = picked.source ?? picked?.attributes?.source ?? null

    const pickedId = picked.id ?? null
    const pickedName = picked.name ?? picked?.attributes?.name ?? picked.title ?? picked?.attributes?.title ?? ''
    const muscleGroup = picked.muscle_group ?? picked?.attributes?.muscle_group ?? null
    const externalSource = picked.external_source ?? picked?.attributes?.external_source ?? null
    const externalId = picked.external_id ?? picked?.attributes?.external_id ?? null

    const isNumericId = pickedId !== null && /^\d+$/.test(String(pickedId))

    if (source === 'local' && isNumericId) {
        exercise.exercise_id = Number(pickedId)
        exercise.external_source = null
        exercise.external_id = null
    } else {
        exercise.exercise_id = null
        exercise.external_source = externalSource || source || 'external'
        exercise.external_id = externalId || pickedId
    }

    exercise.picked_name = pickedName || exercise.picked_name || ''
    exercise.catalog_name = muscleGroup || exercise.catalog_name

    if (!String(exercise.display_name || '').trim()) {
        exercise.display_name = exercise.picked_name
    }
}

function addPickedExercise(picked) {
    const newExercise = makeEmptyExercise(form.exercises.length + 1, '')
    form.exercises.push(newExercise)
    applyPickedToExercise(newExercise, picked)

    closeSearch()
    exerciseSearchQuery.value = ''

    focusExerciseName(newExercise.uid)
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
                    name: String(exerciseItem.display_name || exerciseItem.picked_name || '').trim(),
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

    form.post(route('workouts.store'), {
        data: payload,
        preserveScroll: true
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Workout" />

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create workout</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Add a new workout session.</p>
                    </div>
                    <Link
                        href="/workouts"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Back
                    </Link>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 space-y-6">
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
                                <div v-if="form.errors.date" class="mt-1 text-sm text-red-600">{{ form.errors.date }}</div>
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
                                    <option value="Bike">Bike</option>
                                    <option value="Swim">Swim</option>
                                    <option value="Strength">Strength</option>
                                </select>
                                <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Time (H:m:s)
                                    </label>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 tabular-nums">
                                        {{ durationHms }}
                                    </div>
                                </div>

                                <div class="mt-1 flex items-center gap-2">
                                    <input
                                        v-model.number="durationHours"
                                        type="number"
                                        min="0"
                                        max="999"
                                        step="1"
                                        inputmode="numeric"
                                        placeholder="HH"
                                        class="h-11 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-center tabular-nums"
                                    />
                                    <input
                                        v-model.number="durationMinutes"
                                        type="number"
                                        min="0"
                                        max="59"
                                        step="1"
                                        inputmode="numeric"
                                        placeholder="MM"
                                        class="h-11 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-center tabular-nums"
                                    />
                                    <input
                                        v-model.number="durationSecondsPart"
                                        type="number"
                                        min="0"
                                        max="59"
                                        step="1"
                                        inputmode="numeric"
                                        placeholder="SS"
                                        class="h-11 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-center tabular-nums"
                                    />
                                </div>

                                <div v-if="form.errors.duration_seconds" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.duration_seconds }}
                                </div>
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
                                <div v-if="form.errors.distance_km" class="mt-1 text-sm text-red-600">{{ form.errors.distance_km }}</div>
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
                                <div v-if="form.errors.calories" class="mt-1 text-sm text-red-600">{{ form.errors.calories }}</div>
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
                            <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</div>
                        </div>
                    </div>

                    <div v-if="isStrength" class="rounded-2xl bg-white dark:bg-gray-800 p-6 space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">Exercises</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Add exercises and sets for your strength workout.</div>
                            </div>
                            <button
                                type="button"
                                @click="addExercise"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Add exercise
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div ref="searchBoxRef" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Search exercise
                                    </label>
                                    <button
                                        v-if="isSearchOpen"
                                        type="button"
                                        @click="closeSearch"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                                    >
                                        Close
                                    </button>
                                </div>

                                <div class="flex gap-2">
                                    <input
                                        v-model="exerciseSearchQuery"
                                        @keydown="onSearchKeydown"
                                        type="text"
                                        placeholder="Type to search..."
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    />
                                </div>

                                <div v-if="exerciseSearchLoading && isSearchOpen" class="text-sm text-gray-500 dark:text-gray-400">Loading...</div>
                                <div v-if="exerciseSearchError && isSearchOpen" class="text-sm text-red-600">{{ exerciseSearchError }}</div>

                                <div v-if="isSearchOpen && exerciseSearchResults.length" class="mt-2 space-y-2">
                                    <div
                                        v-for="result in exerciseSearchResults"
                                        :key="`${result.source || result.external_source || 'x'}-${result.id}`"
                                        class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 p-3"
                                    >
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white truncate">{{ result.name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ result.muscle_group || result.external_source || result.source || '-' }}
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                                            @click="addPickedExercise(result)"
                                        >
                                            Use
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-for="(exercise, exerciseIndex) in form.exercises"
                                :key="exercise.uid"
                                class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ exercise.order_no }}. {{ (exercise.display_name || exercise.picked_name) || 'Exercise' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            <span v-if="exercise.picked_name && exercise.display_name && exercise.picked_name !== exercise.display_name">
                                                Catalog: {{ exercise.picked_name }} ·
                                            </span>
                                            {{ exercise.catalog_name || exercise.external_source || '-' }}
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
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Your name</label>
                                        <input
                                            :ref="setExerciseNameRef(exercise.uid)"
                                            v-model="exercise.display_name"
                                            type="text"
                                            placeholder="Exercise name"
                                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                        />
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
                                                <td class="px-3 py-2">
                                                    <input
                                                        v-model.number="setItem.reps"
                                                        type="number"
                                                        min="0"
                                                        step="1"
                                                        inputmode="numeric"
                                                        class="w-24 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                    />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input
                                                        v-model.number="setItem.weight_kg"
                                                        type="number"
                                                        min="0"
                                                        step="0.5"
                                                        inputmode="decimal"
                                                        class="w-28 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                    />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input
                                                        v-model.number="setItem.rir"
                                                        type="number"
                                                        min="0"
                                                        step="1"
                                                        inputmode="numeric"
                                                        class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                    />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input
                                                        v-model.number="setItem.rest_seconds"
                                                        type="number"
                                                        min="0"
                                                        step="1"
                                                        inputmode="numeric"
                                                        class="w-24 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                    />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <button
                                                        type="button"
                                                        @click="removeSet(exerciseIndex, setIndex)"
                                                        class="rounded-lg bg-red-600 px-2 py-1 text-xs font-medium text-white hover:bg-red-700"
                                                    >
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div v-if="form.errors[`exercises.${exerciseIndex}.exercise_id`]" class="text-sm text-red-600">
                                        {{ form.errors[`exercises.${exerciseIndex}.exercise_id`] }}
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
