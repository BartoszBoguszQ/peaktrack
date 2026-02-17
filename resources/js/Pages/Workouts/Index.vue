<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'

const props = defineProps({
    workouts: Object,
    filters: Object,
})

const filtersForm = useForm({
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    type: props.filters?.type ?? '',
    min_distance_km: props.filters?.min_distance_km ?? '',
    max_distance_km: props.filters?.max_distance_km ?? '',
    min_calories: props.filters?.min_calories ?? '',
    max_calories: props.filters?.max_calories ?? '',
})

const applyFilters = () => {
    router.get('/workouts', { ...filtersForm.data() }, { preserveState: true, preserveScroll: true, replace: true })
}

const resetFilters = () => {
    filtersForm.date_from = ''
    filtersForm.date_to = ''
    filtersForm.type = ''
    filtersForm.min_distance_km = ''
    filtersForm.max_distance_km = ''
    filtersForm.min_calories = ''
    filtersForm.max_calories = ''
    router.get('/workouts', {}, { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Treningi" />
        <div class="py-6">
            <div class="max-w-6xl mx-auto px-4">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Workouts</h1>
                    <Link href="/workouts/create" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 text-white px-4 py-2 hover:bg-blue-700">Add workout</Link>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 mb-4">
                    <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                        <div class="md:col-span-1">
                            <label class="block text-xs mb-1 text-gray-600 dark:text-gray-300">From</label>
                            <input v-model="filtersForm.date_from" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs mb-1 text-gray-600 dark:text-gray-300">To</label>
                            <input v-model="filtersForm.date_to" type="date" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs mb-1 text-gray-600 dark:text-gray-300">Type</label>
                            <select v-model="filtersForm.type" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="">All</option>
                                <option value="run">Run</option>
                                <option value="strength">Strength</option>
                                <option value="swim">Swim</option>
                                <option value="ride">Ride</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs mb-1 text-gray-600 dark:text-gray-300">Min km</label>
                            <input v-model="filtersForm.min_distance_km" type="number" min="0" step="0.01" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs mb-1 text-gray-600 dark:text-gray-300">Max km</label>
                            <input v-model="filtersForm.max_distance_km" type="number" min="0" step="0.01" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs mb-1 text-gray-600 dark:text-gray-300">Min kcal</label>
                            <input v-model="filtersForm.min_calories" type="number" min="0" step="1" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs mb-1 text-gray-600 dark:text-gray-300">Max kcal</label>
                            <input v-model="filtersForm.max_calories" type="number" min="0" step="1" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>

                        <div class="md:col-span-4 flex gap-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 text-white px-4 py-2 hover:bg-blue-700">Filter</button>
                            <button type="button" @click="resetFilters" class="inline-flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-white px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">Reset</button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Time</th>
                            <th class="px-4 py-3 text-left">Distance [km]</th>
                            <th class="px-4 py-3 text-left">Calories</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="workoutRow in props.workouts.data"
                            :key="workoutRow.id"
                            class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <td class="px-4 py-3">
                                <Link :href="`/workouts/${workoutRow.id}`" class="text-blue-600 hover:underline">{{ workoutRow.attributes.date }}</Link>
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ workoutRow.attributes.type }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ workoutRow.attributes.duration }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ workoutRow.attributes.distance_km }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ workoutRow.attributes.calories }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.workouts.links" class="flex gap-2 mt-4 flex-wrap">
                    <Link
                        v-for="paginationLink in props.workouts.links"
                        :key="paginationLink.url || paginationLink.label"
                        :href="paginationLink.url || '#'"
                        v-html="paginationLink.label"
                        class="px-3 py-1 rounded border"
                        :class="{'bg-blue-600 text-white': paginationLink.active,'text-gray-700 dark:text-white': !paginationLink.active}"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
