<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    personId: { type: String, required: true },
    relationships: { type: Object, default: () => ({ parents: [], children: [], spouses: [], pending: [] }) },
    peopleList: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

// ── ADD MODAL ──────────────────────────────────────────────────────────────
const showAddModal = ref(false);
const addProcessing = ref(false);
const addForm = ref({
    type: 'parent',
    related_id: '',
    is_biological: true,
    started_at: '',
    ended_at: '',
    ended_reason: '',
});
const addErrors = ref({});

const typeOptions = [
    { value: 'parent', label: 'Orang Tua Kandung' },
    { value: 'child', label: 'Anak Kandung' },
    { value: 'spouse', label: 'Pasangan / Suami / Istri' },
    { value: 'step_parent', label: 'Orang Tua Tiri' },
    { value: 'step_child', label: 'Anak Tiri' },
    { value: 'adopted_parent', label: 'Orang Tua Angkat' },
    { value: 'adopted_child', label: 'Anak Angkat' },
];

const endedReasonOptions = [
    { value: 'divorce', label: 'Cerai' },
    { value: 'death', label: 'Meninggal' },
    { value: 'annulment', label: 'Pembatalan' },
    { value: 'separation', label: 'Pisah' },
    { value: 'disownment', label: 'Diputus' },
];

const isSpouseType = computed(() => addForm.value.type === 'spouse');

const openAdd = () => {
    addForm.value = { type: 'parent', related_id: '', is_biological: true, started_at: '', ended_at: '', ended_reason: '' };
    addErrors.value = {};
    showAddModal.value = true;
};

const submitAdd = () => {
    addErrors.value = {};
    if (!addForm.value.related_id) { addErrors.value.related_id = 'Pilih orang terlebih dahulu.'; return; }

    addProcessing.value = true;
    router.post('/relationships', {
        person_id: props.personId,
        related_id: addForm.value.related_id,
        type: addForm.value.type,
        is_biological: addForm.value.is_biological,
        started_at: addForm.value.started_at || null,
        ended_at: addForm.value.ended_at || null,
        ended_reason: addForm.value.ended_reason || null,
    }, {
        onSuccess: () => { showAddModal.value = false; },
        onError: (errors) => { addErrors.value = errors; },
        onFinish: () => { addProcessing.value = false; },
    });
};

// ── DELETE ─────────────────────────────────────────────────────────────────
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleteReason = ref('');
const deleteProcessing = ref(false);

const confirmDelete = (rel) => {
    deleteTarget.value = rel;
    deleteReason.value = '';
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (deleteReason.value.length < 3) return;
    deleteProcessing.value = true;
    router.delete(`/relationships/${deleteTarget.value.id}`, {
        data: { reason: deleteReason.value },
        onSuccess: () => { showDeleteModal.value = false; deleteTarget.value = null; },
        onFinish: () => { deleteProcessing.value = false; },
    });
};

// ── APPROVE ────────────────────────────────────────────────────────────────
const approveRelationship = (rel) => {
    router.post(`/relationships/${rel.id}/approve`, {}, {
        preserveScroll: true,
    });
};

// ── HELPERS ────────────────────────────────────────────────────────────────
const formatYear = (d) => d ? new Date(d).getFullYear() : null;

const genderBg = (gender) => ({
    male: 'bg-blue-100 text-blue-700',
    female: 'bg-pink-100 text-pink-700',
    unknown: 'bg-gray-100 text-gray-500',
}[gender] ?? 'bg-gray-100 text-gray-500');

const typeLabel = (type) => ({
    parent: 'Orang Tua',
    step_parent: 'Orang Tua Tiri',
    adopted_parent: 'Orang Tua Angkat',
    child: 'Anak',
    step_child: 'Anak Tiri',
    adopted_child: 'Anak Angkat',
    spouse: 'Pasangan',
}[type] ?? type);

const hasAnyRelation = computed(() =>
    props.relationships.parents.length > 0 ||
    props.relationships.children.length > 0 ||
    props.relationships.spouses.length > 0
);
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Relasi Keluarga
            </h3>
            <button v-if="can.manage_relations" @click="openAdd"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Relasi
            </button>
        </div>

        <div class="p-6 space-y-6">

            <!-- pending (admin/mod only) -->
            <div v-if="relationships.pending?.length > 0">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-2">⏳ Menunggu Persetujuan</p>
                <div class="space-y-2">
                    <div v-for="rel in relationships.pending" :key="rel.id"
                        class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <div
                            :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0', genderBg(rel.person?.gender)]">
                            {{ rel.person?.display_name?.charAt(0) ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ rel.person?.display_name }}</p>
                            <p class="text-xs text-amber-600">{{ typeLabel(rel.type) }} · Pending</p>
                        </div>
                        <button v-if="can.approve_relations" @click="approveRelationship(rel)"
                            class="px-2.5 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 rounded-md transition-colors">
                            Setujui
                        </button>
                    </div>
                </div>
            </div>

            <!-- orang tua -->
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Orang Tua</p>
                <div v-if="relationships.parents.length === 0" class="text-sm text-gray-400 italic py-2">Belum ada data
                    orang tua.</div>
                <div v-else class="space-y-2">
                    <div v-for="rel in relationships.parents" :key="rel.id"
                        class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg group">
                        <div
                            :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0', genderBg(rel.person?.gender)]">
                            {{ rel.person?.display_name?.charAt(0) ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <a :href="`/people/${rel.person?.id}`"
                                class="text-sm font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                {{ rel.person?.display_name }}
                            </a>
                            <p class="text-xs text-gray-400">
                                {{ typeLabel(rel.type) }}
                                <span v-if="!rel.is_biological" class="text-gray-300">· non-biologis</span>
                                <span v-if="formatYear(rel.person?.birth_date)"> · {{ formatYear(rel.person.birth_date)
                                    }}</span>
                            </p>
                        </div>
                        <button v-if="can.manage_relations" @click="confirmDelete(rel)"
                            class="p-1.5 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- pasangan -->
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Pasangan</p>
                <div v-if="relationships.spouses.length === 0" class="text-sm text-gray-400 italic py-2">Belum ada data
                    pasangan.</div>
                <div v-else class="space-y-2">
                    <div v-for="rel in relationships.spouses" :key="rel.id"
                        class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg group">
                        <div
                            :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0', genderBg(rel.person?.gender)]">
                            {{ rel.person?.display_name?.charAt(0) ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <a :href="`/people/${rel.person?.id}`"
                                class="text-sm font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                {{ rel.person?.display_name }}
                            </a>
                            <p class="text-xs text-gray-400">
                                Pasangan
                                <span v-if="rel.started_at"> · Menikah {{ new Date(rel.started_at).getFullYear()
                                    }}</span>
                                <span v-if="rel.ended_at" class="text-red-400"> · Berakhir {{ new
                                    Date(rel.ended_at).getFullYear() }}</span>
                            </p>
                        </div>
                        <button v-if="can.manage_relations" @click="confirmDelete(rel)"
                            class="p-1.5 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- anak -->
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Anak</p>
                <div v-if="relationships.children.length === 0" class="text-sm text-gray-400 italic py-2">Belum ada data
                    anak.</div>
                <div v-else class="space-y-2">
                    <div v-for="rel in relationships.children" :key="rel.id"
                        class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg group">
                        <div
                            :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0', genderBg(rel.person?.gender)]">
                            {{ rel.person?.display_name?.charAt(0) ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <a :href="`/people/${rel.person?.id}`"
                                class="text-sm font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                {{ rel.person?.display_name }}
                            </a>
                            <p class="text-xs text-gray-400">
                                {{ typeLabel(rel.type) }}
                                <span v-if="!rel.is_biological" class="text-gray-300">· non-biologis</span>
                                <span v-if="formatYear(rel.person?.birth_date)"> · {{ formatYear(rel.person.birth_date)
                                    }}</span>
                            </p>
                        </div>
                        <button v-if="can.manage_relations" @click="confirmDelete(rel)"
                            class="p-1.5 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- add modal -->
    <Teleport to="body">
        <div v-if="showAddModal"
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Tambah Relasi</h3>
                    <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <!-- jenis relasi -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Jenis Relasi <span class="text-red-500">*</span>
                        </label>
                        <select v-model="addForm.type"
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <!-- pilih orang -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            {{ isSpouseType ? 'Pasangan' : addForm.type.includes('parent') ? 'Orang Tua' : 'Anak' }}
                            <span class="text-red-500">*</span>
                        </label>
                        <select v-model="addForm.related_id" :class="['w-full text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300',
                            addErrors.related_id ? 'border-red-400' : 'border-gray-300']">
                            <option value="">— Pilih anggota —</option>
                            <option v-for="p in peopleList" :key="p.id" :value="p.id">
                                {{ p.display_name }}
                                {{ p.birth_date ? `(${new Date(p.birth_date).getFullYear()})` : '' }}
                                · {{ p.gender === 'male' ? 'L' : p.gender === 'female' ? 'P' : '?' }}
                            </option>
                        </select>
                        <p v-if="addErrors.related_id" class="mt-1 text-xs text-red-500">{{ addErrors.related_id }}</p>
                    </div>

                    <!-- biologis (hanya untuk parent/child) -->
                    <div v-if="!isSpouseType" class="flex items-center gap-2">
                        <input type="checkbox" id="is_bio" v-model="addForm.is_biological"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="is_bio" class="text-sm text-gray-700">Hubungan biologis (kandung)</label>
                    </div>

                    <!-- tanggal mulai -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            {{ isSpouseType ? 'Tanggal Menikah' : 'Tanggal Mulai' }}
                        </label>
                        <input type="date" v-model="addForm.started_at"
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                    </div>

                    <!-- tanggal berakhir + alasan (opsional) -->
                    <div v-if="isSpouseType">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Tanggal Berakhir <span class="text-gray-400">(opsional)</span>
                        </label>
                        <input type="date" v-model="addForm.ended_at"
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                    </div>

                    <div v-if="isSpouseType && addForm.ended_at">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Alasan Berakhir</label>
                        <select v-model="addForm.ended_reason"
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="">— Pilih alasan —</option>
                            <option v-for="opt in endedReasonOptions" :key="opt.value" :value="opt.value">{{ opt.label
                                }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3 px-6 py-4 border-t border-gray-100 justify-end">
                    <button @click="showAddModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button @click="submitAdd" :disabled="addProcessing"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors disabled:opacity-50">
                        {{ addProcessing ? 'Menyimpan...' : 'Simpan Relasi' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- delete modal -->
    <Teleport to="body">
        <div v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Hapus Relasi</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Hapus relasi dengan
                    <span class="font-medium text-gray-700">{{ deleteTarget?.person?.display_name }}</span>?
                </p>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Alasan <span class="text-red-500">*</span>
                    </label>
                    <input v-model="deleteReason" type="text" placeholder="Minimal 3 karakter..."
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300" />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Batal
                    </button>
                    <button @click="submitDelete" :disabled="deleteReason.length < 3 || deleteProcessing"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg disabled:opacity-50">
                        {{ deleteProcessing ? 'Menghapus...' : 'Hapus' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
