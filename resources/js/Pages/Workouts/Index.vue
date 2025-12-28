<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
const props = defineProps({ workouts: Object })
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
                        <tr v-for="row in props.workouts.data" :key="row.id" class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3">
                                <Link :href="`/workouts/${row.id}`" class="text-blue-600 hover:underline">{{ row.attributes.date }}</Link>
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ row.attributes.type }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ row.attributes.duration }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ row.attributes.distance_km }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ row.attributes.calories }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="props.workouts.links" class="flex gap-2 mt-4">
                    <Link v-for="link in props.workouts.links" :key="link.url || link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-1 rounded border" :class="{'bg-blue-600 text-white': link.active,'text-gray-700 dark:text-white': !link.active}" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
