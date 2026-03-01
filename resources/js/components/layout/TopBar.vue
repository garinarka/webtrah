<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

defineProps({
    user: { type: Object, default: null },
    notifications: { type: Array, default: () => [] },
});

const page = usePage();
const showNotifPanel = ref(false);
const notifLoading = ref(false);
const notifList = ref([]);

const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);

// FETCH NOTIF
const loadNotifications = async () => {
    if (notifLoading.value) return;
    notifLoading.value = true;
    try {
        const res = await fetch('/notifications', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        notifList.value = await res.json();
    } catch { /* silent */ }
    notifLoading.value = false;
};

const togglePanel = async () => {
    showNotifPanel.value = !showNotifPanel.value;
    if (showNotifPanel.value) await loadNotifications();
};

// MARK READ
const markRead = async (notif) => {
    if (!notif.read_at) {
        await fetch(`/notifications/${notif.id}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json',
            },
        });
        notif.read_at = new Date().toISOString();
    }
    if (notif.data?.url) {
        showNotifPanel.value = false;
        router.visit(notif.data.url);
    }
};

const markAllRead = async () => {
    await fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
    });
    notifList.value = notifList.value.map(n => ({ ...n, read_at: new Date().toISOString() }));
    router.reload({ only: ['unreadNotificationsCount'] });
};

// CLOSE ON OUTSIDE CLICK
const panelRef = ref(null);
const onDocClick = (e) => {
    if (panelRef.value && !panelRef.value.contains(e.target)) {
        showNotifPanel.value = false;
    }
};
onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));

// HELPERS
const formatTime = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} mnt lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
};

const notifIcon = (type) => ({
    approval_requested: '📋',
    approval_decided: '✅',
}[type] ?? '🔔');

const decidedColor = (notif) => {
    if (notif.data?.type !== 'approval_decided') return '';
    return notif.data?.decision === 'approved' ? 'border-l-green-400' : 'border-l-red-400';
};
</script>

<template>
    <header class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-50">
        <div class="flex items-center justify-between h-full px-4">
            <!-- logo -->
            <Link href="/" class="text-xl font-bold text-indigo-600 tracking-tight">
                Webtrah
            </Link>

            <div class="flex items-center gap-2">
                <!-- notification bell -->
                <div v-if="user" ref="panelRef" class="relative">
                    <button @click.stop="togglePanel"
                        class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Notifikasi">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <!-- unread badge -->
                        <span v-if="unreadCount > 0"
                            class="absolute top-1 right-1 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full leading-none">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>

                    <!-- notification panel -->
                    <Transition enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 scale-95 translate-y-1"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95 translate-y-1">
                        <div v-if="showNotifPanel"
                            class="absolute right-0 top-full mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden z-50"
                            @click.stop>
                            <!-- panel header -->
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Notifikasi</h3>
                                    <p v-if="unreadCount > 0" class="text-xs text-gray-400">{{ unreadCount }} belum
                                        dibaca</p>
                                </div>
                                <button v-if="unreadCount > 0" @click="markAllRead"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                                    Tandai semua dibaca
                                </button>
                            </div>

                            <!-- loading -->
                            <div v-if="notifLoading" class="flex items-center justify-center py-8 text-gray-400">
                                <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span class="text-sm">Memuat...</span>
                            </div>

                            <!-- empty state -->
                            <div v-else-if="notifList.length === 0"
                                class="flex flex-col items-center justify-center py-10 text-gray-400">
                                <svg class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                <p class="text-sm">Tidak ada notifikasi</p>
                            </div>

                            <!-- notification list -->
                            <ul v-else class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
                                <li v-for="notif in notifList" :key="notif.id">
                                    <button @click="markRead(notif)" :class="[
                                        'w-full text-left flex gap-3 px-4 py-3 hover:bg-gray-50 transition-colors border-l-2',
                                        notif.read_at ? 'border-l-transparent' : 'border-l-indigo-500 bg-indigo-50/40',
                                        decidedColor(notif),
                                    ]">
                                        <!-- icon -->
                                        <div
                                            class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-base">
                                            {{ notifIcon(notif.data?.type) }}
                                        </div>
                                        <!-- content -->
                                        <div class="flex-1 min-w-0">
                                            <p
                                                :class="['text-xs leading-snug', notif.read_at ? 'text-gray-600' : 'text-gray-900 font-medium']">
                                                {{ notif.data?.message ?? 'Notifikasi baru' }}
                                            </p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ formatTime(notif.created_at) }}
                                            </p>
                                        </div>
                                        <!-- unread dot -->
                                        <div v-if="!notif.read_at"
                                            class="flex-shrink-0 w-2 h-2 rounded-full bg-indigo-500 mt-1.5" />
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </Transition>
                </div>

                <!-- user avatar -->
                <div v-if="user" class="flex items-center gap-2 pl-1">
                    <span class="text-sm text-gray-600 hidden md:block">{{ user.name }}</span>
                    <div
                        class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-sm cursor-default">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                </div>
                <Link v-else href="/login" class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">Masuk
                </Link>
            </div>
        </div>
    </header>
</template>
