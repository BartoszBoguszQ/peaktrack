<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    endurance: { type: Object, required: true },
    strength: { type: Array, required: true },
})

function formatPace(paceSecondsPerKilometer) {
    if (paceSecondsPerKilometer === null || paceSecondsPerKilometer === undefined) {
        return '-'
    }
    const totalSeconds = Number(paceSecondsPerKilometer) || 0
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = totalSeconds % 60
    return `${minutes}:${String(seconds).padStart(2, '0')}/km`
}

function formatDuration(durationSeconds) {
    if (durationSeconds === null || durationSeconds === undefined) {
        return '-'
    }
    const totalSeconds = Number(durationSeconds) || 0
    const hours = Math.floor(totalSeconds / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    const seconds = totalSeconds % 60

    const parts = []
    if (hours) {
        parts.push(String(hours).padStart(2, '0'))
    }
    parts.push(String(minutes).padStart(2, '0'))
    parts.push(String(seconds).padStart(2, '0'))

    return parts.join(':')
}

function formatDistance(distanceKilometers) {
    if (distanceKilometers === null || distanceKilometers === undefined) {
        return '-'
    }
    return Number(distanceKilometers).toFixed(2)
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Personal Records" />
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Personal Records
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Best results across endurance and strength based on your logged workouts.
                        </p>
                    </div>
                    <Link
                        href="/dashboard"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Back to dashboard
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-4">
                        <div class="font-semibold text-gray-900 dark:text-white">
                            Running PRs
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Longest run
                                </div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ formatDistance(props.endurance.run?.longest?.distance_km) }} km
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDuration(props.endurance.run?.longest?.duration_seconds) }}
                                    <span v-if="props.endurance.run?.longest?.date">
                                        ({{ props.endurance.run.longest.date }})
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Fastest overall pace
                                </div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ formatPace(props.endurance.run?.fastest_overall?.pace_seconds_per_km) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDistance(props.endurance.run?.fastest_overall?.distance_km) }} km
                                    <span v-if="props.endurance.run?.fastest_overall?.date">
                                        ({{ props.endurance.run.fastest_overall.date }})
                                    </span>
                                    <span v-if="props.endurance.run?.fastest_overall?.workout_id">
                                        ,
                                        <Link
                                            :href="route('workouts.show', props.endurance.run.fastest_overall.workout_id)"
                                            class="text-blue-600 hover:underline"
                                        >
                                            view workout
                                        </Link>
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Most calories on a run
                                </div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    <span v-if="props.endurance.run?.max_calories">
                                        {{ props.endurance.run.max_calories.calories }} kcal
                                    </span>
                                    <span v-else>
                                        -
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="props.endurance.run?.max_calories">
                                        {{ formatDistance(props.endurance.run.max_calories.distance_km) }} km
                                        ·
                                        {{ formatDuration(props.endurance.run.max_calories.duration_seconds) }}
                                        <span v-if="props.endurance.run.max_calories.date">
                                            ({{ props.endurance.run.max_calories.date }})
                                        </span>
                                        <span v-if="props.endurance.run.max_calories.workout_id">
                                            ,
                                            <Link
                                                :href="route('workouts.show', props.endurance.run.max_calories.workout_id)"
                                                class="text-blue-600 hover:underline"
                                            >
                                                view workout
                                            </Link>
                                        </span>
                                    </span>
                                    <span v-else>
                                        No running workouts with calories yet.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-4">
                        <div class="font-semibold text-gray-900 dark:text-white">
                            Ride PRs
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Longest ride
                                </div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ formatDistance(props.endurance.ride?.longest?.distance_km) }} km
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDuration(props.endurance.ride?.longest?.duration_seconds) }}
                                    <span v-if="props.endurance.ride?.longest?.date">
                                        ({{ props.endurance.ride.longest.date }})
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Most calories on a ride
                                </div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    <span v-if="props.endurance.ride?.max_calories">
                                        {{ props.endurance.ride.max_calories.calories }} kcal
                                    </span>
                                    <span v-else>
                                        -
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="props.endurance.ride?.max_calories">
                                        {{ formatDistance(props.endurance.ride.max_calories.distance_km) }} km
                                        ·
                                        {{ formatDuration(props.endurance.ride.max_calories.duration_seconds) }}
                                        <span v-if="props.endurance.ride.max_calories.date">
                                            ({{ props.endurance.ride.max_calories.date }})
                                        </span>
                                        <span v-if="props.endurance.ride.max_calories.workout_id">
                                            ,
                                            <Link
                                                :href="route('workouts.show', props.endurance.ride.max_calories.workout_id)"
                                                class="text-blue-600 hover:underline"
                                            >
                                                view workout
                                            </Link>
                                        </span>
                                    </span>
                                    <span v-else>
                                        No rides with calories yet.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 space-y-4 lg:col-span-2">
                        <div class="font-semibold text-gray-900 dark:text-white">
                            Swim PRs
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Longest swim
                                </div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ formatDistance(props.endurance.swim?.longest?.distance_km) }} km
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDuration(props.endurance.swim?.longest?.duration_seconds) }}
                                    <span v-if="props.endurance.swim?.longest?.date">
                                        ({{ props.endurance.swim.longest.date }})
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Most calories on a swim
                                </div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    <span v-if="props.endurance.swim?.max_calories">
                                        {{ props.endurance.swim.max_calories.calories }} kcal
                                    </span>
                                    <span v-else>
                                        -
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="props.endurance.swim?.max_calories">
                                        {{ formatDistance(props.endurance.swim.max_calories.distance_km) }} km
                                        ·
                                        {{ formatDuration(props.endurance.swim.max_calories.duration_seconds) }}
                                        <span v-if="props.endurance.swim.max_calories.date">
                                            ({{ props.endurance.swim.max_calories.date }})
                                        </span>
                                        <span v-if="props.endurance.swim.max_calories.workout_id">
                                            ,
                                            <Link
                                                :href="route('workouts.show', props.endurance.swim.max_calories.workout_id)"
                                                class="text-blue-600 hover:underline"
                                            >
                                                view workout
                                            </Link>
                                        </span>
                                    </span>
                                    <span v-else>
                                        No swims with calories yet.
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Most calories (overall)
                                </div>
                                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    <span v-if="props.endurance.overall?.max_calories">
                                        {{ props.endurance.overall.max_calories.calories }} kcal
                                    </span>
                                    <span v-else>
                                        -
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="props.endurance.overall?.max_calories">
                                        {{ props.endurance.overall.max_calories.type }}
                                        ·
                                        {{ formatDistance(props.endurance.overall.max_calories.distance_km) }} km
                                        ·
                                        {{ formatDuration(props.endurance.overall.max_calories.duration_seconds) }}
                                        <span v-if="props.endurance.overall.max_calories.date">
                                            ({{ props.endurance.overall.max_calories.date }})
                                        </span>
                                        <span v-if="props.endurance.overall.max_calories.workout_id">
                                            ,
                                            <Link
                                                :href="route('workouts.show', props.endurance.overall.max_calories.workout_id)"
                                                class="text-blue-600 hover:underline"
                                            >
                                                view workout
                                            </Link>
                                        </span>
                                    </span>
                                    <span v-else>
                                        No workouts with calories yet.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-gray-800 p-4">
                        <div class="text-sm text-gray-600 dark:text-white mb-2">
                            Strength PRs
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            Best set and estimated one-rep maximum per exercise based on all logged strength workouts.
                        </div>
                        <div class="overflow-x-auto -mx-3">
                            <table class="min-w-full text-xs">
                                <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-3 py-2 text-left">Exercise</th>
                                    <th class="px-3 py-2 text-right">Best [kg]</th>
                                    <th class="px-3 py-2 text-right">Est. 1RM</th>
                                    <th class="px-3 py-2 text-right">Volume</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    v-for="strengthRow in props.strength"
                                    :key="strengthRow.name"
                                    class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">
                                        {{ strengthRow.name }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-white">
                                        {{ strengthRow.best_weight_kg.toFixed(1) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-white">
                                        {{ strengthRow.best_one_rm_kg.toFixed(1) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-white">
                                        {{ Math.round(strengthRow.total_volume) }}
                                    </td>
                                </tr>
                                <tr v-if="!props.strength.length">
                                    <td colspan="4" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No strength workouts yet.
                                    </td>
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
