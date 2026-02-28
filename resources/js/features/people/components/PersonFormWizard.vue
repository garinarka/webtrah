<script setup>
import { watch, onMounted, onBeforeUnmount, ref } from 'vue';
import { usePersonForm } from '../composables/usePersonForm.js';
import { useAutoSave } from '../composables/useAutoSave.js';
import StepBasicInfo from './steps/StepBasicInfo.vue';
import StepDates     from './steps/StepDates.vue';
import StepFamily    from './steps/StepFamily.vue';
import StepReview    from './steps/StepReview.vue';

const props = defineProps({
    initialData:    { type: Object, default: () => ({}) },
    familyUnits:    { type: Array,  default: () => [] },
    isEditMode:     { type: Boolean, default: false },
    personId:       { type: String, default: null },
    submitRoute:    { type: String, required: true },
    submitMethod:   { type: String, default: 'post' },
});

const emit = defineEmits(['submitted']);

const {
    form, currentStep, steps, stepStatus,
    allErrors, nextStep, prevStep, goToStep,
    genderLabel, accuracyLabel, validateStep,
} = usePersonForm({ ...props.initialData, family_unit_id: props.initialData?.family_unit_id ?? props.familyUnits[0]?.id });

const {
    isSaving, lastSavedAt, hasUnsavedChanges, saveError,
    scheduleSave, clearDraft, savedAtFormatted, loadFromLocal,
} = useAutoSave(form, currentStep);

// ── DRAFT RESTORE ──────────────────────────────────────────────────────────

const showDraftRestore = ref(false);
const localDraft       = ref(null);

onMounted(() => {
    if (!props.isEditMode) {
        const draft = loadFromLocal();
        if (draft && !props.initialData?.display_name) {
            localDraft.value     = draft;
            showDraftRestore.value = true;
        }
    }
});

const restoreLocalDraft = () => {
    if (!localDraft.value) return;
    const d = localDraft.value;
    Object.keys(d).forEach(key => {
        if (key in form && key !== 'current_step' && key !== 'saved_at') {
            form[key] = d[key];
        }
    });
    if (d.current_step) currentStep.value = d.current_step;
    showDraftRestore.value = false;
};

const dismissDraft = () => {
    showDraftRestore.value = false;
    clearDraft();
};

// ── AUTO-SAVE WATCH ────────────────────────────────────────────────────────

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

// ── NAVIGATION ─────────────────────────────────────────────────────────────

const handleNext = () => {
    nextStep();
};

const handlePrev = () => {
    prevStep();
};

// ── SUBMIT ─────────────────────────────────────────────────────────────────

const submitAsSubmit = () => {
    if (!validateStep(currentStep.value)) return;
    form.is_draft = false;
    submitForm();
};

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

// ── STEP COMPONENTS MAP ────────────────────────────────────────────────────

const stepComponents = [StepBasicInfo, StepDates, StepFamily, StepReview];
const CurrentStepComponent = (step) => stepComponents[step - 1];

// Warn on unsaved changes
const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};
onMounted(()  => window.addEventListener('beforeunload', handleBeforeUnload));
onBeforeUnmount(() => window.removeEventListener('beforeunload', handleBeforeUnload));
</script>

<template>
    <div class="max-w-2xl mx-auto">
        <!-- ── DRAFT RESTORE BANNER ── -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
        >
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

        <!-- ── PROGRESS STEPS ── -->
        <div class="mb-8">
            <div class="flex items-center justify-between relative">
                <!-- Progress line -->
                <div class="absolute top-5 left-0 right-0 h-0.5 bg-gray-200 z-0">
                    <div
                        class="h-full bg-indigo-500 transition-all duration-500 ease-out"
                        :style="`width: ${((currentStep - 1) / (steps.length - 1)) * 100}%`"
                    />
                </div>

                <!-- Step indicators -->
                <button
                    v-for="step in stepStatus"
                    :key="step.id"
                    type="button"
                    @click="goToStep(step.id)"
                    :disabled="step.id > currentStep"
                    class="relative z-10 flex flex-col items-center gap-1.5 focus:outline-none group"
                    :class="step.id > currentStep ? 'cursor-not-allowed' : 'cursor-pointer'"
                >
                    <div :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-200 shadow-sm',
                        step.current   ? 'bg-indigo-600 text-white ring-4 ring-indigo-100 scale-110' :
                        step.completed ? 'bg-indigo-500 text-white' :
                        step.hasError  ? 'bg-red-100 text-red-600 ring-2 ring-red-300' :
                                         'bg-white border-2 border-gray-200 text-gray-400',
                    ]">
                        <svg v-if="step.completed && !step.current" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        <span v-else>{{ step.id }}</span>
                    </div>
                    <span :class="[
                        'text-xs font-medium transition-colors hidden sm:block',
                        step.current ? 'text-indigo-700' : 'text-gray-400',
                    ]">
                        {{ step.label }}
                    </span>
                </button>
            </div>
        </div>

        <!-- ── FORM CARD ── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Auto-save status bar -->
            <div v-if="!isEditMode" class="flex items-center gap-2 px-6 py-2 bg-gray-50 border-b border-gray-100 text-xs text-gray-400">
                <svg v-if="isSaving" class="animate-spin h-3 w-3 text-indigo-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span v-if="isSaving">Menyimpan draft...</span>
                <span v-else-if="lastSavedAt">
                    ✓ Draft tersimpan pukul {{ savedAtFormatted() }}
                </span>
                <span v-else-if="hasUnsavedChanges">
                    Perubahan belum tersimpan
                </span>
                <span v-else class="text-gray-300">Auto-save aktif</span>

                <span v-if="saveError" class="text-red-400 ml-2">{{ saveError }}</span>
            </div>

            <!-- Step content -->
            <div class="p-6">
                <Transition
                    :name="'slide'"
                    mode="out-in"
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-x-4"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 -translate-x-4"
                >
                    <component
                        :is="stepComponents[currentStep - 1]"
                        :key="currentStep"
                        :form="form"
                        :errors="allErrors"
                        :family-units="familyUnits"
                        :family-unit-id="form.family_unit_id"
                        :gender-label="genderLabel"
                        :accuracy-label="accuracyLabel"
                        :is-edit-mode="isEditMode"
                    />
                </Transition>
            </div>

            <!-- Navigation footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <!-- Left: back button -->
                <button
                    v-if="currentStep > 1"
                    type="button"
                    @click="handlePrev"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali
                </button>
                <div v-else />

                <!-- Right: next / submit -->
                <div class="flex items-center gap-3">
                    <!-- Save as draft (step 4 only) -->
                    <button
                        v-if="currentStep === steps.length && !isEditMode"
                        type="button"
                        @click="submitAsDraft"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                    >
                        💾 Simpan Draft
                    </button>

                    <!-- Next / Submit -->
                    <button
                        v-if="currentStep < steps.length"
                        type="button"
                        @click="handleNext"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
                    >
                        Lanjut
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <button
                        v-else
                        type="button"
                        @click="submitAsSubmit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm disabled:opacity-60"
                    >
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ isEditMode ? 'Ajukan Perubahan' : 'Ajukan Permohonan' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Server errors (non-field) -->
        <div v-if="form.errors && Object.keys(form.errors).length && !Object.values(allErrors).some(Boolean)"
            class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-medium text-red-700 mb-1">Terjadi kesalahan:</p>
            <ul class="text-xs text-red-600 space-y-0.5">
                <li v-for="(err, key) in form.errors" :key="key">• {{ err }}</li>
            </ul>
        </div>
    </div>
</template>
