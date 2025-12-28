<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AnalyticsPreviewChart from "@/Components/AnalyticsPreviewChart.vue";

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean
})

const features = [
    { icon: 'bi-activity', title: 'Endurance workouts', description: 'Running, cycling, swimming with distance and time.' },
    { icon: 'bi-person', title: 'Strength workouts', description: 'Exercises with sets, reps, weight, RIR and rest time.' },
    { icon: 'bi-bar-chart-line', title: 'Analytics', description: 'Weekly and monthly summaries for distance and calories.' },
    { icon: 'bi-trophy', title: 'Personal records', description: 'Best workouts and strength stats in one place.' },
    { icon: 'bi-journal-text', title: 'Workout history', description: 'Clear list view with fast access to details and edits.' },
    { icon: 'bi-shield-lock', title: 'All in one', description: 'All your workouts in one place.' },
]

const valueProps = [
    { label: 'One app' },
    { label: 'Easy to use' },
    { label: 'Clear charts' },
    { label: 'Mobile-friendly' }
]
</script>

<template>
    <Head title="PeakTrack - Monitor and analyze your training" />

    <div
        class="min-h-screen overflow-x-hidden bg-gradient-to-b from-gray-50 to-white text-gray-900 dark:from-gray-950 dark:to-gray-900 dark:text-gray-100"
    >
        <header class="mx-auto max-w-7xl px-4 sm:px-6 py-5 sm:py-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="h-9 w-9 shrink-0 rounded-lg bg-gray-900 dark:bg-white grid place-items-center text-white dark:text-gray-900 font-bold">
                    PT
                </div>
                <span class="text-lg font-semibold truncate">PeakTrack</span>
            </div>

            <nav class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2" v-if="canLogin">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('dashboard')"
                    class="w-full sm:w-auto inline-flex items-center justify-center text-center rounded-xl px-4 py-2 bg-gray-900 text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900"
                >
                    Go to app
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="w-full sm:w-auto inline-flex items-center justify-center text-center rounded-xl px-4 py-2 bg-gray-900 text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="w-full sm:w-auto inline-flex items-center justify-center text-center rounded-xl px-4 py-2 border border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <section class="relative overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 pt-6 sm:pt-8 pb-12 sm:pb-16 grid gap-10 lg:grid-cols-2 lg:items-center">
                <div class="min-w-0">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight break-words">
                        Track and analyze your training in one place
                    </h1>

                    <p class="mt-4 text-base sm:text-lg text-gray-600 dark:text-gray-300 break-words">
                        PeakTrack brings together running, cycling, swimming, and strength training. Add sessions, browse your workout history, and review clear weekly and monthly analytics.
                    </p>

                    <div class="mt-6 grid grid-cols-1 sm:flex sm:flex-wrap gap-3">
                        <Link
                            v-if="canRegister && !$page.props.auth?.user"
                            :href="route('register')"
                            class="w-full sm:w-auto inline-flex items-center justify-center text-center rounded-xl px-5 py-3 bg-indigo-600 text-white hover:bg-indigo-500"
                        >
                            <i class="bi bi-person-plus me-2"></i>Create account
                        </Link>

                        <Link
                            v-if="canLogin && !$page.props.auth?.user"
                            :href="route('login')"
                            class="w-full sm:w-auto inline-flex items-center justify-center text-center rounded-xl px-5 py-3 border border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <i class="bi bi-box-arrow-in-right me-2"></i>Log in
                        </Link>
                    </div>

                    <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                        <div
                            v-for="item in valueProps"
                            :key="item.label"
                            class="min-w-0 rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 text-sm dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div class="font-semibold truncate">{{ item.label }}</div>
                        </div>
                    </div>
                </div>

                <div class="relative min-w-0">
                    <div class="w-full max-w-full rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                        <div class="p-2 sm:p-0">
                            <div class="h-[320px] sm:h-[360px] lg:h-[380px] w-full max-w-full">
                                <AnalyticsPreviewChart />
                            </div>
                        </div>
                    </div>

                    <div class="pointer-events-none absolute -inset-x-10 -bottom-10 -z-10 h-64 bg-gradient-to-t from-indigo-500/10 to-transparent blur-3xl"></div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 sm:px-6 py-10">
            <h2 class="text-xl sm:text-2xl font-bold">What PeakTrack can do</h2>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-start gap-3">
                        <i :class="['bi', feature.icon, 'text-xl', 'mt-1']"></i>
                        <div class="min-w-0">
                            <div class="font-semibold break-words">{{ feature.title }}</div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-300 break-words">{{ feature.description }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 sm:px-6 py-10">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg sm:text-xl font-semibold">How it works</h3>

                <ol class="mt-4 grid gap-3 sm:grid-cols-3">
                    <li class="rounded-2xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                        <div class="font-semibold">
                            <i class="bi bi-plus-circle me-2"></i>Add a workout
                        </div>
                        <div class="text-gray-600 dark:text-gray-300">
                            Fast forms for running, strength training, and other activities.
                        </div>
                    </li>

                    <li class="rounded-2xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                        <div class="font-semibold">
                            <i class="bi bi-journal-text me-2"></i>Review history
                        </div>
                        <div class="text-gray-600 dark:text-gray-300">
                            Browse your workouts, open details, and edit sessions anytime.
                        </div>
                    </li>

                    <li class="rounded-2xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                        <div class="font-semibold">
                            <i class="bi bi-graph-up-arrow me-2"></i>Track progress
                        </div>
                        <div class="text-gray-600 dark:text-gray-300">
                            See trends over time and understand your training consistency.
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 sm:px-6 py-12">
            <div class="rounded-3xl bg-gradient-to-r from-indigo-600 to-violet-600 p-6 sm:p-8 text-white">
                <h3 class="text-xl sm:text-2xl font-bold">Ready for smarter training?</h3>
                <p class="mt-2 text-white/90">Create an account and start tracking progress today.</p>

                <div class="mt-6 grid grid-cols-1 sm:flex sm:flex-wrap gap-3">
                    <Link
                        v-if="canRegister && !$page.props.auth?.user"
                        :href="route('register')"
                        class="w-full sm:w-auto inline-flex items-center justify-center text-center rounded-xl px-5 py-3 bg-white text-gray-900 hover:bg-gray-100"
                    >
                        <i class="bi bi-person-plus me-2"></i>Register
                    </Link>

                    <Link
                        v-if="canLogin && !$page.props.auth?.user"
                        :href="route('login')"
                        class="w-full sm:w-auto inline-flex items-center justify-center text-center rounded-xl px-5 py-3 ring-1 ring-white/70 hover:bg-white/10"
                    >
                        <i class="bi bi-box-arrow-in-right me-2"></i>Log in
                    </Link>
                </div>
            </div>
        </section>

        <footer class="mx-auto max-w-7xl px-4 sm:px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
            <i class="bi bi-c-circle align-text-top me-1"></i>{{ new Date().getFullYear() }} PeakTrack.
        </footer>
    </div>
</template>
