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
    draft_saved: 'Draft Tersimpan',
    delete_requested: 'Hapus Diminta',
    update_requested: 'Ubah Diminta',
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
    <div class="space-y-6">
        <!-- header -->
        <div>
            <h1 class="text-xl font-semibold text-gray-950">Selamat Datang</h1>
            <p class="text-sm text-gray-500 mt-0.5">Jelajahi dan kontribusi ke pohon keluarga</p>
        </div>

        <!-- stat cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-indigo-600 tabular-nums">{{ stats.total_active ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">Anggota Aktif</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ stats.my_submissions ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">Tambahan Saya</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-amber-500 tabular-nums">{{ stats.my_pending ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">Menunggu Approval</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-gray-400 tabular-nums">{{ stats.my_drafts ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">Draft Saya</p>
            </div>
        </div>

        <!-- quick links -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Link href="/people"
                class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Jelajahi Anggota</p>
                    <p class="text-xs text-gray-400">Lihat seluruh anggota keluarga</p>
                </div>
            </Link>

            <Link href="/tree"
                class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Pohon Keluarga</p>
                    <p class="text-xs text-gray-400">Visualisasi silsilah</p>
                </div>
            </Link>

            <Link href="/people/create"
                class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Tambah Data</p>
                    <p class="text-xs text-gray-400">Ajukan anggota baru</p>
                </div>
            </Link>
        </div>

        <!-- my activity -->
        <div v-if="stats.my_activity?.length"
            class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Aktivitas Saya</h2>
            </div>
            <ul class="divide-y divide-gray-50">
                <li v-for="log in stats.my_activity" :key="log.id" class="flex items-center gap-3 px-5 py-3">
                    <span :class="['px-2 py-0.5 rounded text-xs font-medium flex-shrink-0', eventColor(log.event)]">
                        {{ eventLabel(log.event) }}
                    </span>
                    <p class="flex-1 text-sm text-gray-900 truncate">{{ log.model_name }}</p>
                    <span class="text-xs text-gray-400">{{ formatDate(log.created_at) }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
