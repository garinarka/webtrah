<script setup>
import { watch, onMounted, onBeforeUnmount, ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePersonForm } from '../composables/usePersonForm.js';
import { useAutoSave } from '../composables/useAutoSave.js';
import StepBasicInfo from './steps/StepBasicInfo.vue';
import StepDates from './steps/StepDates.vue';
import StepFamily from './steps/StepFamily.vue';
import StepReview from './steps/StepReview.vue';

const props = defineProps({
    initialData: { type: Object, default: () => ({}) },
    familyUnits: { type: Array, default: () => [] },
    isEditMode: { type: Boolean, default: false },
    isDraftMode: { type: Boolean, default: false }, // true = datang dari /people/drafts
    personId: { type: String, default: null },
    submitRoute: { type: String, required: true },
    submitMethod: { type: String, default: 'post' },
});

const emit = defineEmits(['submitted']);

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const {
    form, currentStep, steps, stepStatus,
    allErrors, nextStep, prevStep, goToStep,
    genderLabel, accuracyLabel, validateStep,
    initBirthDate, initDeathDate,
} = usePersonForm({
    ...props.initialData,
    family_unit_id: props.initialData?.family_unit_id ?? props.familyUnits[0]?.id,
    isEditMode: props.isEditMode,
    isAdmin: () => isAdmin.value,
});

const {
    isSaving, lastSavedAt, hasUnsavedChanges, saveError,
    scheduleSave, clearDraft, savedAtFormatted, loadFromLocal,
} = useAutoSave(form, currentStep);

// ── DRAFT RESTORE ─────────────────────────────────────────────────────────────

const showDraftRestore = ref(false);
const localDraft = ref(null);

onMounted(() => {
    if (!props.isEditMode) {
        const draft = loadFromLocal();
        if (draft && !props.initialData?.display_name) {
            localDraft.value = draft;
            showDraftRestore.value = true;
        }
    }
});

const restoreLocalDraft = () => {
    if (!localDraft.value) return;
    const d = localDraft.value;
    if (d.display_name) form.display_name = d.display_name;
    if (d.gender) form.gender = d.gender;
    if (d.birth_date) form.birth_date = d.birth_date;
    if (d.birth_accuracy) form.birth_accuracy = d.birth_accuracy;
    if (d.death_date) form.death_date = d.death_date;
    if (d.death_accuracy) form.death_accuracy = d.death_accuracy;
    if (d.family_unit_id) form.family_unit_id = d.family_unit_id;
    if (d.current_step) currentStep.value = d.current_step;
    showDraftRestore.value = false;
};

const dismissDraft = () => {
    clearDraft();
    showDraftRestore.value = false;
};

// ── CHANGES DETECTION ─────────────────────────────────────────────────────────

const noChangesWarning = ref(false);

const hasChanges = computed(() => {
    // Draft mode: TIDAK perlu cek perubahan — selalu izinkan submit
    if (props.isDraftMode) return true;
    if (!props.isEditMode || !props.initialData) return true;

    const tracked = ['display_name', 'gender', 'birth_date', 'birth_accuracy', 'death_date', 'death_accuracy'];
    return tracked.some(field => {
        let original = props.initialData[field] ?? '';
        // Bandingkan nilai yang sudah diformat (apple-to-apple)
        if (field === 'birth_date') original = initBirthDate;
        else if (field === 'death_date') original = initDeathDate;
        return String(original) !== String(form[field] ?? '');
    });
});

// ── AUTO-SAVE ─────────────────────────────────────────────────────────────────

watch(
    () => form.data?.() ?? {
        display_name: form.display_name, gender: form.gender,
        birth_date: form.birth_date, birth_accuracy: form.birth_accuracy,
        death_date: form.death_date, death_accuracy: form.death_accuracy,
        family_unit_id: form.family_unit_id,
    },
    () => { if (!props.isEditMode) scheduleSave(); },
    { deep: true }
);

// ── NAVIGATION ────────────────────────────────────────────────────────────────

const handleNext = () => { nextStep(); };
const handlePrev = () => { prevStep(); };

// ── SUBMIT HANDLERS ───────────────────────────────────────────────────────────

/**
 * submitAsSubmit: tombol utama
 * - Mode edit biasa: cek hasChanges, submit dengan is_draft=false
 * - Mode draft (isDraftMode): SKIP cek hasChanges
 *   - Admin: kirim dengan is_draft=false → server set status='active'
 *   - Moderator: kirim dengan is_draft=false → server buat approval 'create', status='pending'
 */
const submitAsSubmit = () => {
    if (props.isEditMode && !props.isDraftMode && !hasChanges.value) {
        noChangesWarning.value = true;
        return;
    }
    noChangesWarning.value = false;

    if (!validateStep(currentStep.value)) return;
    form.is_draft = false;
    submitForm();
};

/**
 * submitAsDraft: tombol sekunder
 * - Mode create biasa: simpan sebagai draft baru
 * - Mode draft (isDraftMode): update form fields, pertahankan status='draft'
 */
const submitAsDraft = () => {
    form.is_draft = true;
    submitForm();
};

const submitForm = () => {
    const method = props.submitMethod === 'patch' ? 'patch' : 'post';
    form[method](props.submitRoute, {
        onSuccess: () => {
            clearDraft();
            emit('submitted');
        },
    });
};

// ── PREVENT UNLOAD ────────────────────────────────────────────────────────────

const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value) { e.preventDefault(); e.returnValue = ''; }
};
onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload));
onBeforeUnmount(() => window.removeEventListener('beforeunload', handleBeforeUnload));

// ── LABELS ────────────────────────────────────────────────────────────────────

const submitLabel = computed(() => {
    if (props.isDraftMode) {
        // Mode draft → tombol utama publikasikan data
        return isAdmin.value ? 'Simpan & Aktifkan' : 'Ajukan ke Admin';
    }
    if (props.isEditMode) {
        return isAdmin.value ? 'Simpan Perubahan' : 'Ajukan Perubahan';
    }
    return isAdmin.value ? 'Tambah Anggota' : 'Ajukan Permohonan';
});

// Tombol "Simpan Draft" di step 4 tampil ketika:
// - Create mode (bukan edit): selalu ada
// - Draft mode: ada (untuk update tanpa publish)
const showDraftButton = computed(() =>
    currentStep.value === steps.length && (!props.isEditMode || props.isDraftMode)
);

// ── STEP COMPONENTS ───────────────────────────────────────────────────────────

const stepComponents = [StepBasicInfo, StepDates, StepFamily, StepReview];
</script>

<template>
    <div class="max-w-2xl mx-auto">
        <!-- Draft restore banner (create mode only) -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0">
            <div v-if="showDraftRestore"
                class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4 flex items-center gap-4 shadow-sm">
                <div class="text-2xl flex-shrink-0">💾</div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-indigo-800">Draft ditemukan!</p>
                    <p class="text-xs text-indigo-600 mt-0.5">
                        Tersimpan pada {{ new Date(localDraft?.saved_at).toLocaleString('id-ID') }}
                    </p>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <button @click="restoreLocalDraft"
                        class="px-3 py-1.5 text-xs font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        Lanjutkan
                    </button>
                    <button @click="dismissDraft"
                        class="px-3 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-100 rounded-lg transition-colors">
                        Abaikan
                    </button>
                </div>
            </div>
        </Transition>

        <!-- No-changes warning -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100">
            <div v-if="noChangesWarning"
                class="mb-4 rounded-xl border border-yellow-300 bg-yellow-50 p-4 flex items-start gap-3">
                <span class="text-yellow-500 text-lg flex-shrink-0">⚠️</span>
                <div>
                    <p class="text-sm font-semibold text-yellow-800">Tidak Ada Perubahan</p>
                    <p class="text-xs text-yellow-700 mt-0.5">
                        Data tidak disimpan karena tidak ada perubahan yang terdeteksi.
                        Ubah setidaknya satu field sebelum menyimpan.
                    </p>
                </div>
            </div>
        </Transition>

        <!-- Progress steps -->
        <div class="mb-8">
            <div class="flex items-center justify-between relative">
                <div class="absolute top-5 left-0 right-0 h-0.5 bg-gray-200 z-0">
                    <div class="h-full bg-indigo-500 transition-all duration-500 ease-out"
                        :style="`width: ${((currentStep - 1) / (steps.length - 1)) * 100}%`" />
                </div>
                <button v-for="step in stepStatus" :key="step.id" @click="goToStep(step.id)"
                    :disabled="step.id > currentStep" :class="[
                        'relative z-10 flex flex-col items-center gap-1.5',
                        step.id > currentStep ? 'cursor-not-allowed' : 'cursor-pointer',
                    ]">
                    <div :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all',
                        step.current ? 'bg-indigo-600 border-indigo-600 text-white shadow-md scale-110' :
                            step.completed ? 'bg-indigo-100 border-indigo-400 text-indigo-700' :
                                step.hasError ? 'bg-red-100 border-red-400 text-red-700' :
                                    'bg-white border-gray-300 text-gray-400',
                    ]">
                        <svg v-if="step.completed && !step.current" class="w-5 h-5 text-indigo-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        <span v-else>{{ step.id }}</span>
                    </div>
                    <span :class="[
                        'text-xs font-medium whitespace-nowrap',
                        step.current ? 'text-indigo-700' : 'text-gray-500',
                    ]">{{ step.label }}</span>
                </button>
            </div>
        </div>

        <!-- Auto-save indicator -->
        <div v-if="!isEditMode"
            class="flex items-center gap-2 px-6 py-2 bg-gray-50 border-b border-gray-100 text-xs text-gray-400">
            <template v-if="isSaving">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Menyimpan...
            </template>
            <template v-else-if="lastSavedAt">
                ✓ Tersimpan otomatis {{ savedAtFormatted }}
            </template>
            <template v-else>
                Draft akan tersimpan otomatis saat Anda mengetik
            </template>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Step content -->
            <div class="p-6 md:p-8">
                <component :is="stepComponents[currentStep - 1]" :key="currentStep" :form="form" :errors="allErrors"
                    :family-units="familyUnits" :gender-label="genderLabel" :accuracy-label="accuracyLabel"
                    :is-edit-mode="isEditMode" :is-admin="isAdmin" />
            </div>

            <!-- Footer nav -->
            <div class="px-6 md:px-8 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <!-- Left: back -->
                <button v-if="currentStep > 1" type="button" @click="handlePrev"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </button>
                <div v-else />

                <!-- Right: draft + next/submit -->
                <div class="flex items-center gap-3">

                    <!-- Simpan Draft — tampil di step 4 create mode ATAU draft mode -->
                    <button v-if="showDraftButton" type="button" @click="submitAsDraft" :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Simpan Draft
                    </button>

                    <!-- Next -->
                    <button v-if="currentStep < steps.length" type="button" @click="handleNext"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                        Lanjut
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <!-- Submit utama -->
                    <button v-else type="button" @click="submitAsSubmit" :disabled="form.processing" :class="[
                        'inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-lg transition-colors shadow-sm disabled:opacity-60',
                        isDraftMode ? 'bg-green-600 hover:bg-green-700' : 'bg-indigo-600 hover:bg-indigo-700',
                    ]">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ submitLabel }}
                    </button>

                </div>
            </div>
        </div>

        <!-- Server errors -->
        <div v-if="form.errors && Object.keys(form.errors).length && !Object.values(allErrors).some(Boolean)"
            class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-medium text-red-700 mb-1">Terjadi kesalahan:</p>
            <ul class="text-xs text-red-600 space-y-0.5">
                <li v-for="(err, key) in form.errors" :key="key">• {{ err }}</li>
            </ul>
        </div>
    </div>
</template>
