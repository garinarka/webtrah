<script setup>
import { Head, Link } from '@inertiajs/vue3';

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

const actionLabel = (action) => ({
    create: 'Tambah', update: 'Ubah', delete: 'Hapus',
}[action] ?? action);

const eventLabel = (event) => ({
    created: 'Dibuat', updated: 'Diperbarui', draft_saved: 'Draft',
    delete_requested: 'Hapus Diminta', update_requested: 'Ubah Diminta',
}[event] ?? event);

const eventColor = (event) => ({
    created: 'bg-green-100 text-green-700',
    updated: 'bg-blue-100 text-blue-700',
    draft_saved: 'bg-gray-100 text-gray-600',
    delete_requested: 'bg-red-100 text-red-700',
    update_requested: 'bg-amber-100 text-amber-700',
}[event] ?? 'bg-gray-100 text-gray-600');
</script>

<template>

    <Head title="Moderator" />
    <div class="space-y-6">
        <!-- header -->
        <div>
            <h1 class="text-xl font-semibold text-gray-950">Dashboard Moderator</h1>
            <p class="text-sm text-gray-500 mt-0.5">Pantau dan tinjau perubahan data anggota keluarga</p>
        </div>

        <!-- stat cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="/people"
                class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start gap-4 hover:border-indigo-300 hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-lg bg-indigo-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Total Anggota</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ stats.total_people ?? 0 }}</p>
                    <p class="text-xs text-gray-400">{{ stats.active_people ?? 0 }} aktif</p>
                </div>
            </a>

            <a href="/approvals"
                class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start gap-4 hover:border-amber-300 hover:shadow-md transition-all">
                <div class="w-10 h-10 rounded-lg bg-amber-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Perlu Ditinjau</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">
                        {{ stats.pending_count ?? 0 }}
                        <span v-if="stats.pending_count > 0"
                            class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
                    </p>
                    <p class="text-xs text-gray-400">Approval pending</p>
                </div>
            </a>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Aktivitas 30 Hari</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ stats.recent_changes ?? 0 }}</p>
                    <p class="text-xs text-gray-400">Perubahan data</p>
                </div>
            </div>
        </div>

        <!-- two column: pending + activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- pending -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Menunggu Persetujuan</h2>
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
                    <li v-for="a in stats.pending_approvals" :key="a.id">
                        <Link :href="`/approvals/${a.id}`"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
                            <span :class="[
                                'px-2 py-0.5 rounded text-xs font-medium flex-shrink-0',
                                a.action === 'create' ? 'bg-green-100 text-green-700' :
                                    a.action === 'update' ? 'bg-blue-100 text-blue-700' :
                                        'bg-red-100 text-red-700'
                            ]">{{ actionLabel(a.action) }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ a.person_name }}</p>
                                <p class="text-xs text-gray-400">oleh {{ a.requester }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
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
                    Tinjau Approval
                    <span v-if="stats.pending_count > 0"
                        class="px-1.5 py-0.5 text-xs bg-amber-200 text-amber-800 rounded-full font-bold">
                        {{ stats.pending_count }}
                    </span>
                </Link>
                <Link href="/people/create"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                    Tambah Anggota
                </Link>
                <Link href="/people"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors">
                    Daftar Anggota
                </Link>
            </div>
        </div>
    </div>
</template>
