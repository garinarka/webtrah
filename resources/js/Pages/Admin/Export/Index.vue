<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
});

// status filter untuk export anggota
const exportStatus = ref('active');

const statusOptions = [
    { value: 'active', label: 'Aktif saja' },
    { value: 'all', label: 'Semua status' },
    { value: 'pending', label: 'Pending saja' },
    { value: 'draft', label: 'Draft saja' },
];

// EXPORT CARDS DATA
const exports = [
    {
        id: 'people',
        title: 'Daftar Anggota',
        desc: 'Nama, tanggal lahir, jenis kelamin, status, unit keluarga, dan metadata semua anggota.',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        color: 'indigo',
        format: 'CSV',
        count: null, // pakai stat card
        hasFilter: true,
    },
    {
        id: 'relations',
        title: 'Daftar Relasi',
        desc: 'Semua hubungan keluarga yang sudah disetujui: orang tua–anak dan pasangan.',
        icon: 'M4.5 12.75l6 6 9-13.5',
        color: 'green',
        format: 'CSV',
        count: null,
        hasFilter: false,
    },
    {
        id: 'statistics',
        title: 'Statistik Ringkasan',
        desc: 'Data per bulan, distribusi gender, distribusi dekade kelahiran.',
        icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        color: 'purple',
        format: 'CSV',
        count: null,
        hasFilter: false,
    },
];

const colorMap = {
    indigo: {
        bg: 'bg-indigo-50',
        border: 'border-indigo-200',
        icon: 'bg-indigo-100 text-indigo-600',
        btn: 'bg-indigo-600 hover:bg-indigo-700 text-white',
        badge: 'bg-indigo-100 text-indigo-600',
    },
    green: {
        bg: 'bg-green-50',
        border: 'border-green-200',
        icon: 'bg-green-100 text-green-600',
        btn: 'bg-green-600 hover:bg-green-700 text-white',
        badge: 'bg-green-100 text-green-600',
    },
    purple: {
        bg: 'bg-purple-50',
        border: 'border-purple-200',
        icon: 'bg-purple-100 text-purple-600',
        btn: 'bg-purple-600 hover:bg-purple-700 text-white',
        badge: 'bg-purple-100 text-purple-600',
    },
};

const getExportUrl = (id) => {
    switch (id) {
        case 'people': return `/export/people/csv?status=${exportStatus.value}`;
        case 'relations': return '/export/relations/csv';
        case 'statistics': return '/export/statistics/csv';
        default: return '#';
    }
};
</script>

<template>

    <Head title="Export Data" />
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- header -->
            <div>
                <h1 class="text-xl font-semibold text-gray-950">Export Data</h1>
                <p class="text-sm text-gray-500 mt-0.5">Unduh data keluarga dalam format CSV (dapat dibuka di Excel /
                    Google Sheets)</p>
            </div>

            <!-- stats overview -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600">{{ stats.total_people }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Anggota Aktif</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ stats.total_relations }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Relasi Aktif</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ stats.total_users }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Total Pengguna</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-amber-500">{{ stats.pending_approvals }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Menunggu Approval</p>
                </div>
            </div>

            <!-- format info banner -->
            <div
                class="flex items-start gap-3 px-4 py-3 bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-xl">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <span>
                    File CSV menggunakan encoding <strong>UTF-8 dengan BOM</strong> sehingga langsung bisa dibuka di
                    Excel tanpa perlu konversi.
                    Untuk profil individu, gunakan tombol <strong>Export</strong> di halaman detail anggota.
                </span>
            </div>

            <!-- export cards -->
            <div class="space-y-4">
                <div v-for="exp in exports" :key="exp.id"
                    :class="['bg-white rounded-xl border shadow-sm overflow-hidden', colorMap[exp.color].border]">
                    <div class="p-5 flex items-start gap-4">
                        <!-- icon -->
                        <div
                            :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', colorMap[exp.color].icon]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="exp.icon" />
                            </svg>
                        </div>

                        <!-- info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-semibold text-gray-900">{{ exp.title }}</h3>
                                <span :class="['px-2 py-0.5 text-xs font-bold rounded', colorMap[exp.color].badge]">
                                    {{ exp.format }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ exp.desc }}</p>

                            <!-- status filter for people export -->
                            <div v-if="exp.hasFilter" class="mt-3 flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-medium text-gray-500">Filter:</span>
                                <label v-for="opt in statusOptions" :key="opt.value" :class="[
                                    'flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs cursor-pointer transition-colors',
                                    exportStatus === opt.value
                                        ? 'bg-indigo-600 border-indigo-600 text-white'
                                        : 'bg-white border-gray-200 text-gray-600 hover:border-indigo-300',
                                ]">
                                    <input type="radio" :value="opt.value" v-model="exportStatus" class="sr-only" />
                                    {{ opt.label }}
                                </label>
                            </div>
                        </div>

                        <!-- download button -->
                        <a :href="getExportUrl(exp.id)"
                            :class="['inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg transition-colors flex-shrink-0 mt-0.5', colorMap[exp.color].btn]">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Unduh
                        </a>
                    </div>
                </div>
            </div>

            <!-- individual pdf section -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-start gap-4">
                    <div
                        class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-gray-900">Profil Anggota (Print/PDF)</h3>
                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-rose-100 text-rose-600">HTML →
                                PDF</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Cetak profil lengkap satu anggota beserta data relasi keluarganya.
                            Buka halaman detail anggota → klik tombol <strong>Export</strong> → gunakan fitur cetak
                            browser untuk simpan sebagai PDF.
                        </p>
                        <div class="mt-3">
                            <Link href="/people"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors">
                                Buka Daftar Anggota →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
