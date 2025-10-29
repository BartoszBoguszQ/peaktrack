<script setup>
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean
})

const features = [
    { icon: 'bi-activity', title: 'Treningi wytrzymałościowe', description: 'Bieganie, rower, pływanie.' },
    { icon: 'bi-dumbbell', title: 'Trening siłowy', description: 'Serie, powtórzenia, ciężary' },
    { icon: 'bi-bar-chart-line', title: 'Analiza', description: 'Wykresy postępów, tygodniowe i miesięczne podsumowania.' },
    { icon: 'bi-calendar3', title: 'Planowanie', description: 'Plan tygodnia, cele, przypomnienia.' },
    { icon: 'bi-trophy', title: 'Rekordy i PB', description: 'Najlepsze czasy i wyniki w jednym miejscu.' },
    { icon: 'bi-plug', title: 'Integracje', description: 'Import z zegarków/apek, eksport CSV/GPX.' }
]

const valueProps = [
    { label: 'Jedna aplikacja' },
    { label: 'Prosta w obsłudze' },
    { label: 'Czytelne wykresy' },
    { label: 'Wersja na urządzenia mobilne' }
]
</script>

<template>
    <Head title="PeakTrack – Monitoruj i analizuj trening" />
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-900 dark:from-gray-950 dark:to-gray-900 dark:text-gray-100">
        <header class="mx-auto max-w-7xl px-6 py-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-lg bg-gray-900 dark:bg-white grid place-items-center text-white dark:text-gray-900 font-bold">PT</div>
                <span class="text-lg font-semibold">PeakTrack</span>
            </div>
            <nav class="flex items-center gap-2" v-if="canLogin">
                <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="rounded-xl px-4 py-2 bg-gray-900 text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">Przejdź do aplikacji</Link>
                <template v-else>
                    <Link :href="route('login')" class="rounded-xl px-4 py-2 bg-gray-900 text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">Logowanie</Link>
                    <Link v-if="canRegister" :href="route('register')" class="rounded-xl px-4 py-2 border border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">Rejestracja</Link>
                </template>
            </nav>
        </header>

        <section class="relative overflow-hidden">
            <div class="mx-auto max-w-7xl px-6 pt-8 pb-16 grid gap-10 lg:grid-cols-2 lg:items-center">
                <div>
                    <h1 class="text-4xl font-extrabold leading-tight sm:text-5xl">Monitoruj i analizuj swój trening w jednym miejscu</h1>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">PeakTrack łączy bieganie, rower, pływanie i siłownię. Dodawaj sesje w sekundę, oglądaj czytelne wykresy i planuj tydzień tak, by systematycznie rosnąć.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link v-if="canRegister && !$page.props.auth?.user" :href="route('register')" class="rounded-xl px-5 py-3 bg-indigo-600 text-white hover:bg-indigo-500">
                            <i class="bi bi-person-plus me-2"></i>Załóż konto
                        </Link>
                        <Link v-if="canLogin && !$page.props.auth?.user" :href="route('login')" class="rounded-xl px-5 py-3 border border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Zaloguj się
                        </Link>
                        <Link :href="route('login')" class="rounded-xl px-5 py-3 border border-transparent hover:bg-gray-100 dark:hover:bg-gray-800">
                            <i class="bi bi-play-circle me-2"></i>Zobacz demo
                        </Link>
                    </div>
                    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div v-for="item in valueProps" :key="item.label" class="rounded-2xl border border-gray-200 bg-white p-4 text-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="font-semibold">{{ item.label }}</div>
                            <div class="text-gray-600 dark:text-gray-300">{{ item.value }}</div>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="aspect-[16/10] w-full rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="h-full w-full grid place-items-center text-gray-400">Podgląd wykresów i kart treningu</div>
                    </div>
                    <div class="pointer-events-none absolute -inset-x-10 -bottom-10 -z-10 h-64 bg-gradient-to-t from-indigo-500/10 to-transparent blur-3xl"></div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-10">
            <h2 class="text-2xl font-bold">Co potrafi PeakTrack</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="feature in features" :key="feature.title" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start gap-3">
                        <i :class="['bi', feature.icon, 'text-xl', 'mt-1']"></i>
                        <div>
                            <div class="font-semibold">{{ feature.title }}</div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ feature.description }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-10">
            <div class="rounded-3xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-xl font-semibold">Jak to działa</h3>
                <ol class="mt-4 grid gap-3 sm:grid-cols-3">
                    <li class="rounded-2xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                        <div class="font-semibold"><i class="bi bi-plus-circle me-2"></i>Dodaj trening</div>
                        <div class="text-gray-600 dark:text-gray-300">Szybkie formularze dla biegania, treningów siłowych i innych aktywności.</div>
                    </li>
                    <li class="rounded-2xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                        <div class="font-semibold"><i class="bi bi-graph-up-arrow me-2"></i>Analizuj</div>
                        <div class="text-gray-600 dark:text-gray-300">Tempo, tętno, objętość treningowa.</div>
                    </li>
                    <li class="rounded-2xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                        <div class="font-semibold"><i class="bi bi-calendar-check me-2"></i>Planuj</div>
                        <div class="text-gray-600 dark:text-gray-300">Plan tygodnia i cele z przypomnieniami, żeby trzymać rytm.</div>
                    </li>
                </ol>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-12">
            <div class="rounded-3xl bg-gradient-to-r from-indigo-600 to-violet-600 p-8 text-white">
                <h3 class="text-2xl font-bold">Gotowy na mądrzejsze treningi?</h3>
                <p class="mt-2 text-white/90">Załóż konto i zacznij monitorować postępy już dziś.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <Link v-if="canRegister && !$page.props.auth?.user" :href="route('register')" class="rounded-xl px-5 py-3 bg-white text-gray-900 hover:bg-gray-100">
                        <i class="bi bi-person-plus me-2"></i>Rejestracja
                    </Link>
                    <Link v-if="canLogin && !$page.props.auth?.user" :href="route('login')" class="rounded-xl px-5 py-3 ring-1 ring-white/70 hover:bg-white/10">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Logowanie
                    </Link>
                    <Link :href="route('login')" class="rounded-xl px-5 py-3 ring-1 ring-white/70 hover:bg-white/10">
                        <i class="bi bi-play-circle me-2"></i>Demo
                    </Link>
                </div>
            </div>
        </section>

        <footer class="mx-auto max-w-7xl px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
            © {{ new Date().getFullYear() }} PeakTrack.
        </footer>
    </div>
</template>
