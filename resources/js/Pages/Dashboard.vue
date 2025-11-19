<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const page = usePage()
const stats = ref(page.props?.stats ?? {
    workouts: 12,
    distance_km: 42.8,
    calories: 9350,
    active_days: 7,
})
const recent = ref(page.props?.recent ?? [
    { id: 1, date: '2025-10-12', type: 'Run', duration: '00:32:14', distance_km: 5.2, calories: 410 },
])
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Dashboard" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Workouts</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.workouts }}</div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Distance [km]</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ Number(stats.distance_km).toFixed(2) }}</div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Calories</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.calories }}</div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white">Active days (7d)</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.active_days }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 rounded-2xl bg-white dark:bg-gray-800 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3">
                            <div class="font-semibold text-gray-900 dark:text-white">Recent activities</div>
                            <Link href="/workouts/create" class="text-sm text-blue-600 hover:underline">Add workout</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr class="text-left text-gray-600 dark:text-white">
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Time</th>
                                    <th class="px-4 py-3">Distance [km]</th>
                                    <th class="px-4 py-3">Calories</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="item in recent" :key="item.id ?? item.date + item.type + item.duration" class="border-t border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-3">
                                        <Link :href="`/workouts/${item.id}`" class="text-blue-600 hover:underline">{{ item.date }}</Link>
                                    </td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ item.type }}</td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ item.duration }}</td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ item.distance_km }}</td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ item.calories }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="font-semibold text-gray-900 dark:text-white mb-2">Quick actions</div>
                        <div class="space-y-2">
                            <Link
                                href="/workouts/create"
                                class="w-full inline-flex items-center justify-center rounded-lg bg-blue-600 text-white px-4 py-2 hover:bg-blue-700"
                            >
                                Add workout
                            </Link>
                            <Link
                                href="/workouts"
                                class="w-full inline-flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-2"
                            >
                                Browse workouts
                            </Link>
                            <Link
                                href="/records"
                                class="w-full inline-flex items-center justify-center rounded-lg bg-emerald-600 text-white px-4 py-2 hover:bg-emerald-700"
                            >
                                Best results
                            </Link>
                            <Link
                                :href="route('strength.exercises.index')"
                                class="w-full inline-flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-2"
                            >
                                Strength exercises
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
