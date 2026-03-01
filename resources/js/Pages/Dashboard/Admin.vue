<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});

const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const eventLabel = (event) => ({
    created: 'Dibuat',
    updated: 'Diperbarui',
    draft_saved: 'Draft',
    delete_requested: 'Hapus Diminta',
    update_requested: 'Ubah Diminta',
    deleted: 'Dihapus',
}[event] ?? event);

const eventColor = (event) => ({
    created: 'bg-green-100 text-green-700',
    updated: 'bg-blue-100 text-blue-700',
    draft_saved: 'bg-gray-100 text-gray-600',
    delete_requested: 'bg-red-100 text-red-700',
    update_requested: 'bg-amber-100 text-amber-700',
    deleted: 'bg-red-200 text-red-800',
}[event] ?? 'bg-gray-100 text-gray-600');

const actionLabel = (action) => ({
    create: 'Tambah', update: 'Ubah', delete: 'Hapus',
}[action] ?? action);

const statCards = [
    {
        label: 'Total Anggota',
        key: 'total_people',
        sub: (s) => `${s.active_people} aktif`,
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        bg: 'bg-indigo-500',
        href: '/people',
    },
    {
        label: 'Menunggu Approval',
        key: 'pending_count',
        sub: () => 'Perlu ditinjau',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        bg: 'bg-amber-500',
        href: '/approvals',
        alert: true,
    },
    {
        label: 'Perubahan 30 Hari',
        key: 'recent_changes',
        sub: (s) => `${s.approved_recently} disetujui`,
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        bg: 'bg-green-500',
        href: null,
    },
    {
        label: 'Total Pengguna',
        key: 'total_users',
        sub: () => 'Terdaftar',
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        bg: 'bg-purple-500',
        href: null,
    },
];
</script>

<template>

    <div class="space-y-6">
        <!-- header -->
        <div>
            <h1 class="text-xl font-semibold text-gray-950">Dashboard Admin</h1>
            <p class="text-sm text-gray-500 mt-0.5">Ringkasan kondisi trah keluarga</p>
        </div>

        <!-- stat cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <component :is="card.href ? 'a' : 'div'" v-for="card in statCards" :key="card.key"
                :href="card.href ?? undefined" :class="[
                    'bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start gap-4',
                    card.href ? 'hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer' : '',
                ]">
                <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', card.bg]">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">{{ card.label }}</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5 tabular-nums">
                        {{ stats[card.key] ?? 0 }}
                        <span v-if="card.alert && stats[card.key] > 0"
                            class="ml-1 inline-flex items-center justify-center w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ card.sub(stats) }}</p>
                </div>
            </component>
        </div>

        <!-- bottom grid: pending approvals + recent activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- pending approvals -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Antrian Persetujuan</h2>
                    <Link href="/approvals" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                        Lihat semua →
                    </Link>
                </div>

                <div v-if="!stats.pending_approvals?.length"
                    class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <svg class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm">Tidak ada antrian</p>
                </div>

                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="approval in stats.pending_approvals" :key="approval.id">
                        <Link :href="`/approvals/${approval.id}`"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
                            <span :class="[
                                'px-2 py-0.5 rounded text-xs font-medium flex-shrink-0',
                                approval.action === 'create' ? 'bg-green-100 text-green-700' :
                                    approval.action === 'update' ? 'bg-blue-100 text-blue-700' :
                                        'bg-red-100 text-red-700'
                            ]">
                                {{ actionLabel(approval.action) }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ approval.person_name }}</p>
                                <p class="text-xs text-gray-400">oleh {{ approval.requester }}</p>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0">
                                {{ formatDate(approval.created_at) }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- recent activity -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Aktivitas Terbaru</h2>
                </div>

                <div v-if="!stats.recent_activity?.length"
                    class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <p class="text-sm">Belum ada aktivitas</p>
                </div>

                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="log in stats.recent_activity" :key="log.id" class="flex items-start gap-3 px-5 py-3">
                        <span
                            :class="['px-2 py-0.5 rounded text-xs font-medium flex-shrink-0 mt-0.5', eventColor(log.event)]">
                            {{ eventLabel(log.event) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ log.model_name }}</p>
                            <p class="text-xs text-gray-400">oleh {{ log.user_name }}</p>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0 whitespace-nowrap">
                            {{ formatDate(log.created_at) }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- quick actions -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Aksi Cepat</h2>
            <div class="flex flex-wrap gap-2">
                <Link href="/approvals"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Tinjau Approval
                    <span v-if="stats.pending_count > 0"
                        class="ml-1 px-1.5 py-0.5 text-xs bg-amber-200 text-amber-800 rounded-full font-bold">
                        {{ stats.pending_count }}
                    </span>
                </Link>
                <Link href="/people/create"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Anggota
                </Link>
                <Link href="/people"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Daftar Anggota
                </Link>
                <Link href="/tree"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Pohon Keluarga
                </Link>
            </div>
        </div>
    </div>
</template>
