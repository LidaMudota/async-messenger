<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    showNavigation: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-100">
        <header v-if="showNavigation" class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-10 w-10 fill-current text-gray-500" />
                    <span class="text-sm font-semibold text-gray-700">Home</span>
                </Link>
                <nav class="flex items-center gap-4 text-sm text-gray-600">
                    <Link :href="route('home')" class="hover:text-gray-900">Home</Link>
                    <Link v-if="route().has('login')" :href="route('login')" class="hover:text-gray-900">
                        Login
                    </Link>
                    <Link v-if="route().has('register')" :href="route('register')" class="hover:text-gray-900">
                        Register
                    </Link>
                </nav>
            </div>
        </header>

        <div
            class="flex flex-1 flex-col items-center justify-center pt-6 sm:pt-0"
        >
            <div>
                <Link :href="route('home')">
                    <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
                </Link>
            </div>

            <div
                class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
