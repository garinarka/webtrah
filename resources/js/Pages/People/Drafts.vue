<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    drafts: Object,
    can: Object,
});

// ── DELETE DRAFT ─────────────────────────────────────────────────────────────

const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleteProcessing = ref(false);

const confirmDelete = (person) => {
    deleteTarget.value = person;
    showDeleteModal.value = true;
};

const submitDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/people/${deleteTarget.value.id}`, {
        data: { reason: 'Hapus draft' },
        onSuccess: () => { showDeleteModal.value = false; deleteTarget.value = null; },
        onFinish: () => { deleteProcessing.value = false; },
    });
};

// ── HELPERS ───────────────────────────────────────────────────────────────────

const genderConfig = (gender) => ({
    male: { label: 'Laki-laki', bg: 'bg-blue-100 text-blue-700' },
    female: { label: 'Perempuan', bg: 'bg-pink-100 text-pink-700' },
    unknown: { label: 'Tidak diketahui', bg: 'bg-gray-100 text-gray-500' },
}[gender] ?? { label: 'Tidak diketahui', bg: 'bg-gray-100 text-gray-500' });

const formatYear = (dateStr) => {
    if (!dateStr) return null;
    return parseInt(String(dateStr).split('T')[0].split('-')[0], 10);
};

const formatRelativeTime = (dateStr) => {
    if (!dateStr) return '';
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'baru saja';
    if (mins < 60) return `${mins} menit lalu`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs} jam lalu`;
    const days = Math.floor(hrs / 24);
    if (days < 30) return `${days} hari lalu`;
    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>

    <Head title="Draft Saya" />
    <AppLayout>
        <div class="space-y-5">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                        <Link href="/people" class="hover:text-indigo-600 transition-colors">Anggota Keluarga</Link>
                        <span class="text-gray-300">/</span>
                        <span class="text-gray-800 font-medium">Draft Saya</span>
                    </div>
                    <h1 class="text-xl font-semibold text-gray-950">Draft Saya</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ drafts.total }} data tersimpan sebagai draft
                    </p>
                </div>
                <Link href="/people/create"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Anggota
                </Link>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.message"
                class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                {{ $page.props.flash.message }}
            </div>

            <!-- Empty state -->
            <div v-if="drafts.data.length === 0"
                class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-20 text-center">
                <div class="flex flex-col items-center gap-3 text-gray-400">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <p class="text-base font-medium text-gray-500">Belum ada draft tersimpan</p>
                    <p class="text-sm">Data yang disimpan sebagai draft akan muncul di sini</p>
                    <Link href="/people/create"
                        class="mt-2 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors">
                        Buat Data Baru
                    </Link>
                </div>
            </div>

            <!-- Draft list -->
            <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-amber-50 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <p class="text-xs text-amber-700 font-medium">
                        Data draft belum dikirim dan tidak tampil di daftar utama. Buka dan selesaikan untuk
                        mengajukannya.
                    </p>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-for="person in drafts.data" :key="person.id"
                        class="group flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition-colors">

                        <!-- Avatar -->
                        <div
                            :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0', genderConfig(person.gender).bg]">
                            {{ person.display_name.charAt(0).toUpperCase() }}
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ person.display_name }}</p>
                            <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                                <span class="text-xs text-gray-400">{{ genderConfig(person.gender).label }}</span>
                                <span v-if="person.birth_date" class="text-xs text-gray-400">
                                    Lahir {{ formatYear(person.birth_date) }}
                                </span>
                                <span v-if="person.family_unit" class="text-xs text-gray-400">
                                    {{ person.family_unit.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Last edited -->
                        <div class="hidden sm:block text-right flex-shrink-0">
                            <p class="text-xs text-gray-400">Terakhir disimpan</p>
                            <p class="text-xs text-gray-600 font-medium">{{ formatRelativeTime(person.updated_at) }}</p>
                        </div>

                        <!-- Actions -->
                        <div
                            class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                            <!-- Lanjutkan edit draft -->
                            <Link :href="`/people/${person.id}/edit?mode=draft`"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                                Lanjutkan
                            </Link>

                            <!-- Hapus draft -->
                            <button @click="confirmDelete(person)"
                                class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                title="Hapus draft">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="drafts.last_page > 1"
                    class="px-4 py-3 border-t border-gray-200 flex items-center justify-between bg-gray-50/50">
                    <p class="text-xs text-gray-500">
                        Menampilkan {{ drafts.from }}–{{ drafts.to }} dari {{ drafts.total }} draft
                    </p>
                    <div class="flex items-center gap-1">
                        <Link v-for="(link, i) in drafts.links" :key="i" :href="link.url ?? '#'" :class="[
                            'px-2.5 py-1 rounded-md text-xs font-medium transition-colors',
                            link.active ? 'bg-indigo-600 text-white'
                                : link.url ? 'text-gray-600 hover:bg-gray-100'
                                    : 'text-gray-300 cursor-not-allowed',
                        ]" v-html="link.label" />
                    </div>
                </div>
            </div>

        </div>

        <!-- Delete confirm modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Hapus Draft?</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Draft <span class="font-medium text-gray-700">{{ deleteTarget?.display_name }}</span>
                                akan dihapus permanen.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-5 justify-end">
                        <button @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button @click="submitDelete" :disabled="deleteProcessing"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg disabled:opacity-50">
                            {{ deleteProcessing ? 'Menghapus...' : 'Hapus Draft' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
