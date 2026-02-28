<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import RelationshipsPanel from './Partials/RelationshipsPanel.vue';

const props = defineProps({
    person: Object,
    auditLogs: { type: Array, default: () => [] },
    relationships: { type: Object, default: () => ({ parents: [], children: [], spouses: [], pending: [] }) },
    peopleList: { type: Array, default: () => [] },
    can: Object,
});

// ── DATE FORMATTING ──────────────────────────────────────────────────────────
const formatDate = (rawDate, accuracy) => {
    if (!rawDate) return null;
    // strip timezone noise — ambil hanya bagian tanggal saja
    const dateOnly = String(rawDate).split('T')[0];
    if (!dateOnly || accuracy === 'unknown') return null;

    try {
        // gunakan date string murni agar tidak kena timezone shift
        const [year, month, day] = dateOnly.split('-').map(Number);
        const d = new Date(year, month - 1, day);

        if (accuracy === 'year') return String(year);
        if (accuracy === 'year_month') return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long' });
        return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch {
        return dateOnly;
    }
};

const accuracyLabel = (accuracy) => ({
    exact: null,
    year_month: 'perkiraan bulan',
    year: 'perkiraan tahun',
    unknown: null,
}[accuracy] ?? null);

const isDeceased = props.person.death_date != null;

// ── STATUS CONFIG ────────────────────────────────────────────────────────────
const statusConfig = (status) => ({
    active: { label: 'Aktif', cls: 'bg-green-50 text-green-700 ring-green-600/20' },
    pending: { label: 'Pending', cls: 'bg-amber-50 text-amber-700 ring-amber-600/20' },
    draft: { label: 'Draft', cls: 'bg-gray-50 text-gray-600 ring-gray-500/20' },
    disputed: { label: 'Disputed', cls: 'bg-red-50 text-red-700 ring-red-600/20' },
    archived: { label: 'Arsip', cls: 'bg-gray-100 text-gray-500 ring-gray-400/20' },
}[status] ?? { label: status, cls: 'bg-gray-50 text-gray-600 ring-gray-500/20' });

const genderLabel = (gender) => ({
    male: 'Laki-laki', female: 'Perempuan', unknown: 'Tidak Diketahui',
}[gender] ?? '-');

// ── DELETE MODAL ─────────────────────────────────────────────────────────────
const showDeleteModal = ref(false);
const deleteReason = ref('');
const deleteProcessing = ref(false);

const submitDelete = () => {
    if (deleteReason.value.length < 5) return;
    deleteProcessing.value = true;
    router.delete(`/people/${props.person.id}`, {
        data: { reason: deleteReason.value },
        onFinish: () => { deleteProcessing.value = false; },
    });
};

// ── AUDIT LOG ────────────────────────────────────────────────────────────────
const eventLabel = (event) => ({
    created: 'Dibuat',
    updated: 'Diperbarui',
    draft_saved: 'Draft Tersimpan',
    delete_requested: 'Hapus Diminta',
    update_requested: 'Ubah Diminta',
    deleted: 'Dihapus',
}[event] ?? event);

const eventColor = (event) => ({
    created: 'text-green-700 bg-green-50 ring-green-600/20',
    updated: 'text-blue-700 bg-blue-50 ring-blue-600/20',
    draft_saved: 'text-gray-600 bg-gray-50 ring-gray-500/20',
    delete_requested: 'text-red-700 bg-red-50 ring-red-600/20',
    update_requested: 'text-amber-700 bg-amber-50 ring-amber-600/20',
    deleted: 'text-red-800 bg-red-100 ring-red-700/20',
}[event] ?? 'text-gray-600 bg-gray-50 ring-gray-500/20');
</script>

<template>

    <Head :title="person.display_name" />
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- flash message -->
            <div v-if="$page.props.flash?.message"
                class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                {{ $page.props.flash.message }}
            </div>

            <!-- breadcrumb + actions header -->
            <div class="flex items-center justify-between">
                <nav class="flex items-center gap-2 text-sm text-gray-500">
                    <Link href="/people" class="hover:text-indigo-600 transition-colors">Anggota Keluarga</Link>
                    <span class="text-gray-300">/</span>
                    <span class="text-gray-800 font-medium">{{ person.display_name }}</span>
                </nav>
                <div class="flex items-center gap-2">
                    <Link v-if="can.edit" :href="`/people/${person.id}/edit`"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                        Edit
                    </Link>
                    <button v-if="can.delete" @click="showDeleteModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>

            <!-- main profile card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- header gradient -->
                <div class="px-6 py-6 bg-gradient-to-br from-indigo-600 to-indigo-500 flex items-center gap-5">
                    <div :class="[
                        'w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-bold text-white flex-shrink-0',
                        person.gender === 'male' ? 'bg-blue-400/60' : person.gender === 'female' ? 'bg-pink-400/60' : 'bg-indigo-300/60'
                    ]">
                        {{ person.display_name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ person.display_name }}</h1>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span
                                :class="['inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold ring-1 ring-inset', statusConfig(person.status).cls]">
                                {{ statusConfig(person.status).label }}
                            </span>
                            <span v-if="isDeceased" class="text-indigo-200 text-xs">† Almarhum/Almarhumah</span>
                        </div>
                    </div>
                </div>

                <!-- detail fields -->
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                        <!-- gender -->
                        <div>
                            <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ genderLabel(person.gender) }}</dd>
                        </div>

                        <!-- tanggal Lahir -->
                        <div>
                            <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <template v-if="formatDate(person.birth_date, person.birth_accuracy)">
                                    <span class="font-medium">{{ formatDate(person.birth_date, person.birth_accuracy)
                                        }}</span>
                                    <span v-if="accuracyLabel(person.birth_accuracy)"
                                        class="text-xs text-gray-400 ml-1">
                                        ({{ accuracyLabel(person.birth_accuracy) }})
                                    </span>
                                </template>
                                <span v-else class="text-gray-400 italic">Tidak diketahui</span>
                            </dd>
                        </div>

                        <!-- tanggal meninggal — hanya tampil jika meninggal -->
                        <div v-if="isDeceased">
                            <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tanggal Meninggal
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <template v-if="formatDate(person.death_date, person.death_accuracy)">
                                    <span class="font-medium">{{ formatDate(person.death_date, person.death_accuracy)
                                        }}</span>
                                    <span v-if="accuracyLabel(person.death_accuracy)"
                                        class="text-xs text-gray-400 ml-1">
                                        ({{ accuracyLabel(person.death_accuracy) }})
                                    </span>
                                </template>
                                <span v-else class="text-gray-400 italic">Tidak diketahui</span>
                            </dd>
                        </div>

                        <!-- unit keluarga -->
                        <div>
                            <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Unit Keluarga</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ person.family_unit?.name ?? '—' }}
                            </dd>
                        </div>

                        <!-- dibuat oleh -->
                        <div>
                            <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Dibuat Oleh</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ person.creator?.name ?? 'System' }}</dd>
                        </div>

                        <!-- tanggal dibuat -->
                        <div>
                            <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tanggal Dibuat</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ new Date(person.created_at).toLocaleDateString('id-ID', {
                                    year: 'numeric', month:
                                        'long', day: 'numeric'
                                })
                                }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- relationships panel -->
            <RelationshipsPanel :person-id="person.id" :relationships="relationships" :people-list="peopleList"
                :can="can" />

            <!-- audit log -->
            <div v-if="auditLogs.length > 0" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Riwayat Perubahan
                </h3>
                <div class="space-y-3">
                    <div v-for="log in auditLogs" :key="log.id"
                        class="flex items-start gap-3 pb-3 border-b border-gray-50 last:border-0 last:pb-0">
                        <span
                            :class="['px-2 py-0.5 text-xs font-medium rounded-md ring-1 ring-inset flex-shrink-0 mt-0.5', eventColor(log.event)]">
                            {{ eventLabel(log.event) }}
                        </span>
                        <div>
                            <p class="text-xs text-gray-600">
                                oleh <span class="font-medium text-gray-800">{{ log.user?.name ?? 'System' }}</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ new Date(log.created_at).toLocaleString('id-ID', {
                                    day: '2-digit', month: 'short', year: 'numeric',
                                    hour: '2-digit', minute: '2-digit'
                                }) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- delete modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900">Hapus Anggota</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Hapus <span class="font-medium text-gray-700">{{ person.display_name }}</span>?
                                Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <div class="mt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Alasan penghapusan <span class="text-red-500">*</span>
                                </label>
                                <textarea v-model="deleteReason" rows="2" placeholder="Minimal 5 karakter..."
                                    class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400" />
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-5 justify-end">
                        <button @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button @click="submitDelete" :disabled="deleteReason.length < 5 || deleteProcessing"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors disabled:opacity-50">
                            {{ deleteProcessing ? 'Menghapus...' : 'Ya, Hapus' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
