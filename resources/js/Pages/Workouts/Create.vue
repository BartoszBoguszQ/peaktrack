<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'

const form = useForm({
    date: new Date().toISOString().slice(0,10),
    type: 'Run',
    duration_seconds: 0,
    distance_km: 0,
    calories: 0,
    notes: '',
    exercises: []
})

const durationHms = ref('00:00:00')

function toSeconds(hms) {
    const parts = (hms || '00:00:00').split(':').map(n => parseInt(n || 0, 10))
    const hours = parts[0] || 0
    const minutes = parts[1] || 0
    const seconds = parts[2] || 0
    return hours * 3600 + minutes * 60 + seconds
}

const isStrength = computed(() => form.type === 'Strength')

function addExercise() {
    form.exercises.push({
        exercise_id: null,
        external_source: null,
        external_id: null,
        name: '',
        order_no: form.exercises.length + 1,
        sets: [],
        searchText: '',
        searchResults: [],
        isSearching: false,
        picked: false
    })
}

function removeExercise(exerciseIndex) {
    form.exercises.splice(exerciseIndex, 1)
    form.exercises.forEach((exerciseItem, index) => {
        exerciseItem.order_no = index + 1
    })
}

function addSet(exerciseIndex) {
    const exerciseItem = form.exercises[exerciseIndex]
    exerciseItem.sets.push({
        set_no: exerciseItem.sets.length + 1,
        reps: 1,
        weight_kg: null,
        rir: null,
        rest_seconds: null
    })
}

function removeSet(exerciseIndex, setIndex) {
    const exerciseItem = form.exercises[exerciseIndex]
    exerciseItem.sets.splice(setIndex, 1)
    exerciseItem.sets.forEach((setItem, index) => {
        setItem.set_no = index + 1
    })
}

function quickSets(exerciseIndex, setsCount, repsCount) {
    const exerciseItem = form.exercises[exerciseIndex]
    exerciseItem.sets = Array.from({ length: setsCount }, (_, index) => ({
        set_no: index + 1,
        reps: repsCount,
        weight_kg: null,
        rir: null,
        rest_seconds: null
    }))
}

let searchDebounceTimer = null
async function searchExercises(exerciseIndex) {
    const exerciseItem = form.exercises[exerciseIndex]
    const query = exerciseItem.searchText?.trim() || ''
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
    exerciseItem.isSearching = true
    searchDebounceTimer = setTimeout(async () => {
        try {
            if (query.length < 2) {
                exerciseItem.searchResults = []
                return
            }
            const response = await axios.get('/api/exercises/search', { params: { query } })
            exerciseItem.searchResults = response?.data?.data || []
        } finally {
            exerciseItem.isSearching = false
        }
    }, 300)
}

function pickExercise(exerciseIndex, pickedItem) {
    const exerciseItem = form.exercises[exerciseIndex]
    if (pickedItem.source === 'local') {
        exerciseItem.exercise_id = parseInt(pickedItem.id, 10)
        exerciseItem.external_source = null
        exerciseItem.external_id = null
    } else {
        exerciseItem.exercise_id = null
        exerciseItem.external_source = pickedItem.external_source || 'exercisedb'
        exerciseItem.external_id = pickedItem.external_id || pickedItem.id
    }
    exerciseItem.name = pickedItem.name || ''
    exerciseItem.searchResults = []
    exerciseItem.picked = true
}

function clearPicked(exerciseIndex) {
    const exerciseItem = form.exercises[exerciseIndex]
    exerciseItem.exercise_id = null
    exerciseItem.external_source = null
    exerciseItem.external_id = null
    exerciseItem.name = ''
    exerciseItem.searchText = ''
    exerciseItem.picked = false
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
        exercises: form.type === 'Strength'
            ? form.exercises.map(exerciseItem => ({
                exercise_id: exerciseItem.exercise_id,
                external_source: exerciseItem.external_source,
                external_id: exerciseItem.external_id,
                name: exerciseItem.name,
                order_no: exerciseItem.order_no,
                sets: exerciseItem.sets.map(setItem => ({
                    set_no: setItem.set_no,
                    reps: setItem.reps,
                    weight_kg: setItem.weight_kg,
                    rir: setItem.rir,
                    rest_seconds: setItem.rest_seconds
                }))
            }))
            : []
    }

    form.transform(() => payload).post('/workouts')
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Add workout" />
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Add workout</h1>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-white mb-1">Date</label>
                            <input v-model="form.date" type="date" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-white mb-1">Type</label>
                            <select v-model="form.type" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white">
                                <option>Run</option>
                                <option>Ride</option>
                                <option>Swim</option>
                                <option>Strength</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-white mb-1">Time [HH:MM:SS]</label>
                            <input v-model="durationHms" type="time" step="1" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" />
                        </div>
                        <div v-if="!isStrength">
                            <label class="block text-sm text-gray-600 dark:text-white mb-1">Distance [km]</label>
                            <input v-model.number="form.distance_km" type="number" min="0" step="0.01" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-white mb-1">Calories</label>
                            <input v-model.number="form.calories" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 dark:text-white mb-1">Notes</label>
                        <textarea v-model="form.notes" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" rows="3"></textarea>
                    </div>

                    <div v-if="isStrength" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">Exercises</div>
                            <button type="button" @click="addExercise" class="rounded-lg bg-blue-600 text-white px-3 py-2 hover:bg-blue-700">Add exercise</button>
                        </div>

                        <div v-for="(exerciseItem, exerciseIndex) in form.exercises" :key="exerciseIndex" class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-2 space-y-2">
                                    <label class="block text-sm text-gray-600 dark:text-white mb-1">Search exercise</label>
                                    <input v-model="exerciseItem.searchText" @input="searchExercises(exerciseIndex)" type="text" placeholder="Type at least 2 characters..." class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" />
                                    <div v-if="exerciseItem.isSearching" class="text-xs text-gray-500 mt-1">Searching...</div>
                                    <div v-if="exerciseItem.searchResults.length" class="mt-1 rounded-lg border bg-white dark:bg-gray-900 max-h-48 overflow-auto">
                                        <div v-for="resultItem in exerciseItem.searchResults" :key="resultItem.source + ':' + resultItem.id" class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" @click="pickExercise(exerciseIndex, resultItem)">
                                            {{ resultItem.name }} <span class="text-xs text-gray-500">({{ resultItem.source }})</span>
                                        </div>
                                    </div>
                                    <div v-if="exerciseItem.picked" class="text-sm text-gray-900 dark:text-white">
                                        Selected: <span class="font-medium">{{ exerciseItem.name }}</span>
                                        <button type="button" class="ml-2 text-red-600 hover:underline" @click="clearPicked(exerciseIndex)">Clear</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-white mb-1">Order</label>
                                    <input v-model.number="exerciseItem.order_no" type="number" min="1" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" @click="quickSets(exerciseIndex, 3, 10)" class="px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white">3×10</button>
                                <button type="button" @click="quickSets(exerciseIndex, 4, 8)" class="px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white">4×8</button>
                                <button type="button" @click="quickSets(exerciseIndex, 5, 5)" class="px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white">5×5</button>
                                <button type="button" @click="addSet(exerciseIndex)" class="ml-auto rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 px-3 py-2 text-gray-900 dark:text-white">Add set</button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-white">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Set</th>
                                        <th class="px-3 py-2 text-left">Reps</th>
                                        <th class="px-3 py-2 text-left">Weight [kg]</th>
                                        <th class="px-3 py-2 text-left">RIR</th>
                                        <th class="px-3 py-2 text-left">Rest [s]</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(setItem, setIndex) in exerciseItem.sets" :key="setIndex" class="border-t border-gray-100 dark:border-gray-700">
                                        <td class="px-3 py-2 text-gray-900 dark:text-white">{{ setItem.set_no }}</td>
                                        <td class="px-3 py-2"><input v-model.number="setItem.reps" type="number" min="1" class="w-24 rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" /></td>
                                        <td class="px-3 py-2"><input v-model.number="setItem.weight_kg" type="number" min="0" step="0.25" class="w-28 rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" /></td>
                                        <td class="px-3 py-2"><input v-model.number="setItem.rir" type="number" min="0" max="10" class="w-20 rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" /></td>
                                        <td class="px-3 py-2"><input v-model.number="setItem.rest_seconds" type="number" min="0" class="w-24 rounded-lg border-gray-300 dark:bg-gray-800 dark:text-white" /></td>
                                        <td class="px-3 py-2"><button type="button" @click="removeSet(exerciseIndex, setIndex)" class="text-red-600 hover:underline">Remove</button></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-right">
                                <button type="button" @click="removeExercise(exerciseIndex)" class="text-red-600 hover:underline">Remove exercise</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button :disabled="form.processing" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 text-white px-4 py-2 hover:bg-blue-700">Save</button>
                        <Link href="/workouts" class="text-gray-600 dark:text-white">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
