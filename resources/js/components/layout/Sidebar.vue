<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: Array,
    open: Boolean
});
defineEmits(['toggle']);
</script>

<template>
    <aside class="fixed left-0 top-16 bottom-0 bg-white border-r border-gray-200 transition-all duration-200 z-40"
        :class="open ? 'w-64' : 'w-16'">
        <div class="flex flex-col h-full">
            <button @click="$emit('toggle')" class="p-4 text-gray-400 hover:text-gray-500">
                <svg v-if="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <nav class="flex-1 px-2 space-y-1">
                <Link v-for="item in items" :key="item.name" :href="item.href"
                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md"
                    :class="$page.url.startsWith(item.href) ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                    <span class="truncate">{{ open ? item.name : item.name.charAt(0) }}</span>
                    <span v-if="item.badge && open"
                        class="ml-auto bg-red-100 text-red-600 py-0.5 px-2 rounded-full text-xs">{{ item.badge }}</span>
                </Link>
            </nav>
            <div v-if="$slots.extra && open" class="border-t border-gray-200 pt-4">
                <slot name="extra" />
            </div>
        </div>
    </aside>
</template>
