<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-lg bg-gray-900 dark:bg-white grid place-items-center text-white dark:text-gray-900 font-bold">PT</div>
                    <span class="text-lg font-semibold">PeakTrack</span>
                </div>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
