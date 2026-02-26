<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    user: Object,
    notifications: { type: Array, default: () => [] }
});
</script>

<template>
    <header class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-50">
        <div class="flex items-center justify-between h-full px-4">
            <Link href="/" class="text-xl font-bold text-indigo-600">Family Tree</Link>
            <div class="flex items-center space-x-4">
                <button class="relative p-2 text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span v-if="notifications.length"
                        class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
                </button>
                <div v-if="user" class="flex items-center space-x-2">
                    <span class="text-sm text-gray-700 hidden md:block">{{ user.name }}</span>
                    <div
                        class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold">
                        {{ user.name.charAt(0) }}</div>
                </div>
                <Link v-else href="/login" class="text-sm text-indigo-600 hover:text-indigo-500">Masuk</Link>
            </div>
        </div>
    </header>
</template>
