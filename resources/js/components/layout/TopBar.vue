<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

defineProps({
    user: { type: Object, default: null },
})

const page = usePage()
const showNotifPanel = ref(false)
const showUserMenu = ref(false)
const notifLoading = ref(false)
const notifList = ref([])

// COUNT
// kelola count LOKAL agar tidak bergantung router.reload() yang tidak reliabel
// dengan partial props. Count di-init dari server, update sinkron saat aksi user.
const localUnreadCount = ref(page.props.unreadNotificationsCount ?? 0)

// sync dari server apabila halaman berubah (navigasi, reload penuh, dll)
watch(
    () => page.props.unreadNotificationsCount,
    (val) => {
        localUnreadCount.value = val ?? 0
    },
    { immediate: true },
)

// HTTP HELPER
// gunakan window.axios yang sudah dikonfigurasi oleh Inertia/Laravel.
// axios secara otomatis membaca cookie XSRF-TOKEN (yang di-set Laravel) dan
// mengirimnya sebagai header X-XSRF-TOKEN — CSRF aman TANPA meta tag.
const http = {
    post: (url) => window.axios?.post(url).catch(() => {}),
}

// FETCH NOTIFICATIONS

const loadNotifications = async () => {
    if (notifLoading.value) return
    notifLoading.value = true
    try {
        const res = await fetch('/notifications', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        if (res.ok) {
            const data = await res.json()
            notifList.value = data
            // sinkronkan count lokal dengan data aktual dari server
            localUnreadCount.value = data.filter((n) => !n.read_at).length
        }
    } catch {
        /* silent */
    }
    notifLoading.value = false
}

const togglePanel = async () => {
    showNotifPanel.value = !showNotifPanel.value
    if (showNotifPanel.value) await loadNotifications()
}

// MARK READ

/**
 * klik notif:
 * 1. optimistic update: tandai read di list + kurangi count lokal
 * 2. POST ke server via axios (XSRF cookie) — tidak butuh meta tag
 * 3. navigasi ke URL (kalau ada)
 *
 * count TIDAK perlu router.reload() karena kita kelola lokal.
 * saat navigasi ke halaman baru, server akan mengembalikan count segar
 * via HandleInertiaRequests dan watch() di atas akan sinkronkan.
 */
const markRead = async (notif) => {
    const wasUnread = !notif.read_at

    if (wasUnread) {
        // optimistic: update lokal langsung
        notif.read_at = new Date().toISOString()
        localUnreadCount.value = Math.max(0, localUnreadCount.value - 1)
        // server update — fire-and-forget pakai axios (XSRF cookie otomatis)
        http.post(`/notifications/${notif.id}/read`)
    }

    showNotifPanel.value = false

    if (notif.data?.url) {
        router.visit(notif.data.url)
    }
}

/**
 * tandai semua dibaca:
 * 1. optimistic update semua item + reset count lokal ke 0
 * 2. POST ke server — await agar DB terupdate sebelum panel bisa dibuka lagi
 */
const markAllRead = async () => {
    if (notifList.value.every((n) => n.read_at)) return

    // optimistic update
    notifList.value = notifList.value.map((n) => ({
        ...n,
        read_at: n.read_at ?? new Date().toISOString(),
    }))
    localUnreadCount.value = 0

    // await agar server ter-update; jika gagal, loadNotifications() saat
    // panel dibuka kembali akan sinkronkan ulang dari server
    await http.post('/notifications/read-all')
}

// CLOSE ON OUTSIDE CLICK

const panelRef = ref(null)
const userMenuRef = ref(null)
const onDocClick = (e) => {
    if (panelRef.value && !panelRef.value.contains(e.target)) {
        showNotifPanel.value = false
    }
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        showUserMenu.value = false
    }
}
onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))

// HELPERS

const formatTime = (iso) => {
    if (!iso) return ''
    const diff = (Date.now() - new Date(iso).getTime()) / 1000
    if (diff < 60) return 'Baru saja'
    if (diff < 3600) return `${Math.floor(diff / 60)} mnt lalu`
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`
    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    })
}

const notifIcon = (type) =>
    ({ approval_requested: '📋', approval_decided: '✅' })[type] ?? '🔔'
const decidedColor = (notif) => {
    if (notif.data?.type !== 'approval_decided') return ''
    return notif.data?.decision === 'approved'
        ? 'border-l-green-400'
        : 'border-l-red-400'
}
</script>

<template>
    <header
        class="fixed left-0 right-0 top-0 z-50 h-16 border-b border-gray-200 bg-white"
    >
        <div class="flex h-full items-center justify-between px-4">
            <Link
                href="/"
                class="text-xl font-bold tracking-tight text-indigo-600"
                >Webtrah</Link
            >

            <div class="flex items-center gap-2">
                <!-- notification bell -->
                <div v-if="user" ref="panelRef" class="relative">
                    <button
                        @click.stop="togglePanel"
                        class="relative rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                        title="Notifikasi"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                            />
                        </svg>
                        <!-- badge pakai localUnreadCount — update sinkron, tidak butuh router.reload() -->
                        <span
                            v-if="localUnreadCount > 0"
                            class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold leading-none text-white"
                        >
                            {{ localUnreadCount > 9 ? '9+' : localUnreadCount }}
                        </span>
                    </button>

                    <!-- panel -->
                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 scale-95 translate-y-1"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95 translate-y-1"
                    >
                        <div
                            v-if="showNotifPanel"
                            class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
                            @click.stop
                        >
                            <!-- header -->
                            <div
                                class="flex items-center justify-between border-b border-gray-100 px-4 py-3"
                            >
                                <div>
                                    <h3
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Notifikasi
                                    </h3>
                                    <p
                                        v-if="localUnreadCount > 0"
                                        class="text-xs text-gray-400"
                                    >
                                        {{ localUnreadCount }} belum dibaca
                                    </p>
                                </div>
                                <button
                                    v-if="localUnreadCount > 0"
                                    @click="markAllRead"
                                    class="text-xs font-medium text-indigo-600 transition-colors hover:text-indigo-800"
                                >
                                    Tandai semua dibaca
                                </button>
                            </div>

                            <!-- loading -->
                            <div
                                v-if="notifLoading"
                                class="flex items-center justify-center py-8 text-gray-400"
                            >
                                <svg
                                    class="mr-2 h-5 w-5 animate-spin"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                    />
                                </svg>
                                <span class="text-sm">Memuat...</span>
                            </div>

                            <!-- empty state -->
                            <div
                                v-else-if="notifList.length === 0"
                                class="flex flex-col items-center justify-center py-10 text-gray-400"
                            >
                                <svg
                                    class="mb-2 h-8 w-8"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                                    />
                                </svg>
                                <p class="text-sm">Tidak ada notifikasi</p>
                            </div>

                            <!-- list -->
                            <ul
                                v-else
                                class="max-h-80 divide-y divide-gray-50 overflow-y-auto"
                            >
                                <li v-for="notif in notifList" :key="notif.id">
                                    <button
                                        @click="markRead(notif)"
                                        :class="[
                                            'flex w-full gap-3 border-l-2 px-4 py-3 text-left transition-colors hover:bg-gray-50',
                                            notif.read_at
                                                ? 'border-l-transparent'
                                                : 'border-l-indigo-500 bg-indigo-50/40',
                                            decidedColor(notif),
                                        ]"
                                    >
                                        <div
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-base"
                                        >
                                            {{ notifIcon(notif.data?.type) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p
                                                :class="[
                                                    'text-xs leading-snug',
                                                    notif.read_at
                                                        ? 'text-gray-600'
                                                        : 'font-medium text-gray-900',
                                                ]"
                                            >
                                                {{
                                                    notif.data?.message ??
                                                    'Notifikasi baru'
                                                }}
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-gray-400"
                                            >
                                                {{
                                                    formatTime(notif.created_at)
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            v-if="!notif.read_at"
                                            class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-500"
                                        />
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </Transition>
                </div>

                <!-- user avatar + dropdown -->
                <div v-if="user" ref="userMenuRef" class="relative pl-1">
                    <button
                        @click.stop="showUserMenu = !showUserMenu"
                        class="flex items-center gap-2 rounded-lg px-1 py-1 transition-colors hover:bg-gray-100"
                    >
                        <span class="hidden text-sm text-gray-600 md:block">{{
                            user.name
                        }}</span>
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700"
                        >
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                    </button>

                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 scale-95 translate-y-1"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95 translate-y-1"
                    >
                        <div
                            v-if="showUserMenu"
                            class="absolute right-0 top-full z-50 mt-2 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
                            @click.stop
                        >
                            <div class="border-b border-gray-100 px-4 py-3">
                                <p
                                    class="truncate text-sm font-medium text-gray-900"
                                >
                                    {{ user.name }}
                                </p>
                                <p class="truncate text-xs text-gray-400">
                                    {{ user.email }}
                                </p>
                            </div>
                            <Link
                                href="/profile"
                                @click="showUserMenu = false"
                                class="block px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                Profil Saya
                            </Link>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                @click="showUserMenu = false"
                                class="block w-full px-4 py-2.5 text-left text-sm text-red-600 transition-colors hover:bg-red-50"
                            >
                                Keluar
                            </Link>
                        </div>
                    </Transition>
                </div>
                <Link
                    v-else
                    href="/login"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >Masuk
                </Link>
            </div>
        </div>
    </header>
</template>
