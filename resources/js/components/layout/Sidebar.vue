<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    items: Array,
    open: Boolean,
})
defineEmits(['toggle'])

const page = usePage()

const isActive = (href) => {
    const url = page.url
    // Exact match untuk dashboard agar tidak highlight semua halaman
    if (href === '/dashboard') return url === '/dashboard' || url === '/'
    return url.startsWith(href)
}

// SVG path map — satu path per icon key
// Menggunakan Heroicons outline style (24x24 viewBox, stroke-based)
const iconPaths = {
    Home: 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
    Users: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
    House: 'M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819',
    Tree: 'M12 3v1m0 16v1M3 12h1m16 0h1m-1.343-7.657-.707.707M4.05 19.95l.707-.707m0-15.556.707.707M19.95 19.95l-.707-.707M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7Z',
    Clipboard:
        'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4',
}

const getIconPath = (iconKey) => iconPaths[iconKey] ?? iconPaths['Home']
</script>

<template>
    <aside
        class="fixed bottom-0 left-0 top-16 z-40 flex flex-col border-r border-gray-200 bg-white transition-all duration-200"
        :class="open ? 'w-64' : 'w-16'"
    >
        <!-- Toggle button -->
        <button
            @click="$emit('toggle')"
            class="flex flex-shrink-0 items-center p-4 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600"
            :class="open ? 'justify-end' : 'justify-start'"
            :title="open ? 'Tutup sidebar' : 'Buka sidebar'"
        >
            <!-- Collapse icon -->
            <svg
                v-if="open"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                />
            </svg>
            <!-- Expand icon -->
            <svg
                v-else
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 6h16M4 12h16M4 18h16"
                />
            </svg>
        </button>

        <!-- Navigation -->
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-1">
            <Link
                v-for="item in items"
                :key="item.name"
                :href="item.href"
                :class="[
                    'group flex items-center gap-3 rounded-lg px-2 py-2.5 text-sm font-medium transition-colors',
                    isActive(item.href)
                        ? 'bg-indigo-50 text-indigo-700'
                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                ]"
                :title="!open ? item.name : undefined"
            >
                <!-- Icon -->
                <svg
                    class="h-5 w-5 flex-shrink-0 transition-colors"
                    :class="
                        isActive(item.href)
                            ? 'text-indigo-600'
                            : 'text-gray-400 group-hover:text-gray-600'
                    "
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.75"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :d="getIconPath(item.icon)"
                    />
                </svg>

                <!-- Label — hanya tampil saat expanded -->
                <span v-if="open" class="flex-1 truncate">{{ item.name }}</span>

                <!-- Badge (notif count) -->
                <span
                    v-if="item.badge && open"
                    class="ml-auto flex-shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-600"
                >
                    {{ item.badge }}
                </span>
                <!-- Badge dot saat collapsed -->
                <span
                    v-else-if="item.badge && !open"
                    class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500"
                />
            </Link>
        </nav>
    </aside>
</template>
