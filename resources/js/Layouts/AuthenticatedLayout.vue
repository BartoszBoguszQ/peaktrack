<script setup>
import { ref, watchEffect } from 'vue'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import { Link } from '@inertiajs/vue3'

const showingNavigationDropdown = ref(false)

const isDark = ref(document.documentElement.classList.contains('dark'))
watchEffect(() => {
    document.documentElement.classList.toggle('dark', isDark.value)
    localStorage.setItem('isDark', isDark.value ? '1' : '0')
})
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-950">
            <nav class="border-b border-gray-100 bg-white dark:bg-gray-900 dark:border-gray-800">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                    class="dark:text-white"
                                >
                                    Dashboard
                                </NavLink>

                                <NavLink
                                    :href="route('workouts.index')"
                                    :active="route().current('workouts.*')"
                                    class="dark:text-white"
                                >
                                    Workouts
                                </NavLink>

                                <NavLink
                                    :href="route('analytics.index')"
                                    :active="route().current('analytics.index')"
                                    class="dark:text-white"
                                >
                                    Endurance Analytics
                                </NavLink>

                                <NavLink
                                    :href="route('strength.exercises.index')"
                                    :active="route().current('strength.exercises.index')"
                                    class="dark:text-white"
                                >
                                    Strength Analytics
                                </NavLink>

                                <NavLink
                                    :href="route('records.index')"
                                    :active="route().current('records.index')"
                                    class="dark:text-white"
                                >
                                    Personal Records
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <button
                                @click="isDark = !isDark"
                                class="me-3 inline-flex items-center rounded-md border px-2.5 py-2 text-sm font-medium
                                     border-gray-200 text-gray-700 hover:bg-gray-50
                                     dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                aria-label="Toggle dark mode"
                                title="Przełącz tryb"
                            >
                                <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                     viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Zm0 4a1 1 0 0 1-1-1v-1a1 1 0 1 1 2 0v1a1 1 0 0 1-1 1Zm0-18a1 1 0 0 1-1-1V2a1 1 0 1 1 2 0v1a1 1 0 0 1-1 1Z"/>
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                     fill="currentColor">
                                    <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 1 0 9.79 9.79Z"/>
                                </svg>
                            </button>

                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent dark:text-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('workouts.index')"
                            :active="route().current('workouts.*')"
                        >
                            Workouts
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('analytics.index')"
                            :active="route().current('analytics.index')"
                        >
                            Endurance Analytics
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('strength.exercises.index')"
                            :active="route().current('strength.exercises.index')"
                        >
                            Strength Analytics
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('records.index')"
                            :active="route().current('records.index')"
                        >
                            Personal Records
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header
                class="bg-white shadow dark:bg-gray-900 dark:shadow-none dark:border-b dark:border-gray-800"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="text-gray-900 dark:text-gray-100">
                <slot />
            </main>
        </div>
    </div>
</template>
