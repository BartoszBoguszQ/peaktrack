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
    {date: '2025-10-11', type: 'Siłownia', duration: '00:45:00', distance_km: 0, calories: 520},
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
                    <h2 class="text-2xl font-bold text-gray-900">Cześć, {{ $page.props.auth.user.name }}</h2>
                    <p class="text-sm text-gray-500">Twój przegląd aktywności</p>
                </div>
                <div class="flex gap-2">
                    <button
                        class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
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
                    <div class="rounded-2xl border border-gray-200 bg-white p-4">
                        <div class="text-sm text-gray-500">Treningi</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ stats.workouts }}</div>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4">
                        <div class="text-sm text-gray-500">Dystans [km]</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ stats.distance_km }}</div>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4">
                        <div class="text-sm text-gray-500">Kalorie</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ stats.calories }}</div>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4">
                        <div class="text-sm text-gray-500">Aktywne dni</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ stats.active_days }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 rounded-2xl border border-gray-200 bg-white overflow-hidden">
                        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                            <div class="font-semibold text-gray-900">Ostatnie aktywności</div>
                            <button class="text-sm text-gray-600 hover:text-gray-900">Zobacz wszystkie</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                <tr class="text-left text-gray-600">
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
                                <tr v-for="(r,i) in recent" :key="i" class="border-t border-gray-100">
                                    <td class="px-4 py-2 text-gray-900">{{ r.date }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ r.type }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ r.duration }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ r.distance_km }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ r.calories }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white">
                        <div class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-900">Mój profil</div>
                        <div class="px-4 py-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-12 rounded-full bg-gray-200"></div>
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                                </div>
                            </div>
                            <a href="/profile"
                               class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Edytuj
                                profil</a>
                            <div class="grid grid-cols-3 gap-2 pt-2">
                                <div class="rounded-xl bg-gray-50 p-3 text-center">
                                    <div class="text-xs text-gray-500">Dni z rzędu</div>
                                    <div class="text-xl font-bold text-gray-900">3</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-3 text-center">
                                    <div class="text-xs text-gray-500">PB 5 km</div>
                                    <div class="text-xl font-bold text-gray-900">24:12</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-3 text-center">
                                    <div class="text-xs text-gray-500">Waga</div>
                                    <div class="text-xl font-bold text-gray-900">78 kg</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
