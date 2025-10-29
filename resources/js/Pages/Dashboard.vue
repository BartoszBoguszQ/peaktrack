<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {Head} from '@inertiajs/vue3'
import {ref} from 'vue'

const stats = ref({
    workouts: 12,
    distance_km: 42.8,
    calories: 9350,
    active_days: 7,
})

const recent = ref([
    {date: '2025-10-12', type: 'Bieg', duration: '00:32:14', distance_km: 5.2, calories: 410},
    {date: '2025-10-11', type: 'Trening siłowy', duration: '00:45:00', distance_km: 0, calories: 520},
    {date: '2025-10-10', type: 'Rower', duration: '01:05:33', distance_km: 21.3, calories: 690},
    {date: '2025-10-08', type: 'Marsz', duration: '00:28:05', distance_km: 3.1, calories: 180},
])
</script>

<template>
    <Head title="Dashboard"/>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Cześć, {{ $page.props.auth.user.name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-white">Twój przegląd aktywności</p>
                </div>
                <div class="flex gap-2">
                    <button
                        class="inline-flex items-center rounded-xl bg-white dark:bg-gray-950 dark:text-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Dodaj trening
                    </button>
                    <button
                        class="inline-flex items-center rounded-xl bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Importuj GPX
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-500 dark:text-white">Treningi</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.workouts }}</div>
                    </div>
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-500 dark:text-white">Dystans [km]</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.distance_km }}</div>
                    </div>
                    <div class="rounded-2xl bg-white p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-white">Kalorie</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.calories }}</div>
                    </div>
                    <div class="rounded-2xl bg-white p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-white">Aktywne dni</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.active_days }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 rounded-2xl bg-white dark:bg-gray-800 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3">
                            <div class="font-semibold text-gray-900 dark:text-white">Ostatnie aktywności</div>
                            <button class="text-sm text-gray-600 hover:text-gray-900 dark:text-white">Zobacz wszystkie</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr class="text-left text-gray-600 dark:text-white">
                                    <th class="px-4 py-2">Data</th>
                                    <th class="px-4 py-2">Typ</th>
                                    <th class="px-4 py-2">Czas</th>
                                    <th class="px-4 py-2">Dystans</th>
                                    <th class="px-4 py-2">Kalorie</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-if="!recent.length">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Brak danych</td>
                                </tr>
                                <tr v-for="(r,i) in recent" :key="i">
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ r.date }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-white">{{ r.type }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-white">{{ r.duration }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-white">{{ r.distance_km }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-white">{{ r.calories }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-gray-800">
                        <div class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Mój profil</div>
                        <div class="px-4 py-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-12 rounded-full bg-gray-200"></div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</div>
                                </div>
                            </div>
                            <a href="/profile"
                               class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-700">Edytuj
                                profil</a>
                            <div class="grid grid-cols-3 gap-2 pt-2">
                                <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-3 text-center">
                                    <div class="text-xs text-gray-500 dark:text-white">Dni z rzędu</div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">3</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-3 text-center">
                                    <div class="text-xs text-gray-500 dark:text-white">PB 5 km</div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">24:12</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-900">
                                    <div class="text-xs text-gray-500 dark:text-white">Waga</div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">78 kg</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
