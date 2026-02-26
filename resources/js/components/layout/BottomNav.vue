<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ items: Array });
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
        <div class="flex justify-around items-center h-16">
            <Link v-for="item in items" :key="item.name" :href="item.href"
                class="flex flex-col items-center px-3 py-2 text-xs font-medium"
                :class="$page.url.startsWith(item.href) ? 'text-indigo-600' : 'text-gray-500'">
                <svg class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>{{ item.name }}</span>
            </Link>
        </div>
    </nav>
</template>
