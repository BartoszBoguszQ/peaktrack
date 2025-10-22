<!-- resources/js/Layouts/AppLayout.vue -->
<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, watchEffect } from 'vue'
const isDark = ref(localStorage.getItem('isDark') === '1')
watchEffect(() => {
    document.documentElement.classList.toggle('dark', isDark.value)
    localStorage.setItem('isDark', isDark.value ? '1' : '0')
})
</script>

<template>
    <div :class="isDark ? 'bg-gray-950 text-white' : 'bg-gray-50 text-gray-900'">
        <header :class="['h-14 border-b flex items-center', isDark?'border-gray-800':'border-gray-200']">
            <div class="max-w-7xl mx-auto w-full px-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/" class="font-bold">PeakTrack</Link>
                    <nav class="hidden sm:flex items-center gap-3 text-sm">
                        <Link href="/dashboard" class="hover:underline">Dashboard</Link>
                    </nav>
                </div>
                <button @click="isDark=!isDark" class="px-3 py-1 rounded border" :class="isDark?'border-gray-700':'border-gray-300'">
                    {{ isDark ? 'Dark' : 'Light' }}
                </button>
            </div>
        </header>
        <main class="max-w-7xl mx-auto px-4 py-6">
            <slot />
        </main>
    </div>
</template>
