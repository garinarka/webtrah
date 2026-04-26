<script setup>
import { watch, computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { usePersonForm } from '../composables/usePersonForm.js'
import { useAutoSave } from '../composables/useAutoSave.js'
import StepBasicInfo from './steps/StepBasicInfo.vue'
import StepDates from './steps/StepDates.vue'
import StepFamily from './steps/StepFamily.vue'
import StepReview from './steps/StepReview.vue'

const props = defineProps({
    initialData: { type: Object, default: () => ({}) },
    familyUnits: { type: Array, default: () => [] },
    allPeople: { type: Array, default: () => [] },
    isEditMode: { type: Boolean, default: false },
    isDraftMode: { type: Boolean, default: false },
    lockedFamilyUnitId: { type: String, default: null },
    personId: { type: String, default: null },
    submitRoute: { type: String, required: true },
    submitMethod: { type: String, default: 'post' },
})

const emit = defineEmits(['submitted'])
const page = usePage()
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')

// ── FORM SETUP ────────────────────────────────────────────────────────────────

const {
    form,
    currentStep,
    steps,
    stepStatus,
    allErrors,
    nextStep,
    prevStep,
    goToStep,
    genderLabel,
    accuracyLabel,
    validateStep,
    initBirthDate,
    initDeathDate,
} = usePersonForm({
    ...props.initialData,
    family_unit_id:
        props.lockedFamilyUnitId ??
        props.initialData?.family_unit_id ??
        props.familyUnits[0]?.id,
    isEditMode: props.isEditMode,
    isAdmin: () => isAdmin.value,
})

const {
    isSaving,
    lastSavedAt,
    hasUnsavedChanges,
    scheduleSave,
    clearDraft,
    savedAtFormatted,
    loadFromLocal,
} = useAutoSave(form, currentStep)

// ── STEP FILTERING ────────────────────────────────────────────────────────────
// Kalau lockedFamilyUnitId ada → skip step "family" (sudah pasti unitnya)

const ALL_STEP_COMPONENTS = [StepBasicInfo, StepDates, StepFamily, StepReview]
const ALL_STEP_KEYS = ['basic', 'dates', 'family', 'review']

const visibleStepKeys = computed(() =>
    props.lockedFamilyUnitId
        ? ALL_STEP_KEYS.filter((k) => k !== 'family')
        : ALL_STEP_KEYS,
)

// Map currentStep → komponen yang benar sesuai visibleStepKeys
const currentStepComponent = computed(() => {
    const key = visibleStepKeys.value[currentStep.value - 1]
    const idx = ALL_STEP_KEYS.indexOf(key)
    return ALL_STEP_COMPONENTS[idx] ?? StepBasicInfo
})

// stepStatus hanya untuk step yang terlihat
// id di-remap ke posisi urutan (1, 2, 3) bukan ID asli step (1, 2, 3, 4)
// agar progress indicator dan navigasi selalu menampilkan nomor berurut yang benar
const visibleStepStatus = computed(() =>
    stepStatus.value
        .filter((s) => visibleStepKeys.value.includes(s.key))
        .map((s, index) => ({
            ...s,
            id: index + 1,
            current: currentStep.value === index + 1,
            completed: s.completed && currentStep.value > index + 1,
        })),
)

const totalSteps = computed(() => visibleStepKeys.value.length)

// Terjemahkan posisi step saat ini (1-based positional) ke ID asli STEPS (1,2,3,4).
// Diperlukan karena usePersonForm.js menyimpan step dengan ID tetap (1=basic, 2=dates, 3=family, 4=review)
// sedangkan currentStep adalah posisi urutan yang terlihat (1,2,3 untuk 3-step mode).
const actualStepId = computed(() => {
    const key = visibleStepKeys.value[currentStep.value - 1]
    return ALL_STEP_KEYS.indexOf(key) + 1
})

// ── DRAFT RESTORE ─────────────────────────────────────────────────────────────

const showDraftRestore = ref(false)
const localDraft = ref(null)

onMounted(() => {
    if (!props.isEditMode) {
        const draft = loadFromLocal()
        if (draft && !props.initialData?.display_name) {
            localDraft.value = draft
            showDraftRestore.value = true
        }
    }
})

const restoreLocalDraft = () => {
    if (!localDraft.value) return
    const d = localDraft.value
    if (d.display_name) form.display_name = d.display_name
    if (d.gender) form.gender = d.gender
    if (d.birth_date) form.birth_date = d.birth_date
    if (d.birth_accuracy) form.birth_accuracy = d.birth_accuracy
    if (d.death_date) form.death_date = d.death_date
    if (d.death_accuracy) form.death_accuracy = d.death_accuracy
    if (!props.lockedFamilyUnitId && d.family_unit_id)
        form.family_unit_id = d.family_unit_id
    if (d.current_step) currentStep.value = d.current_step
    showDraftRestore.value = false
}

const dismissDraft = () => {
    clearDraft()
    showDraftRestore.value = false
}

// ── CHANGES DETECTION ─────────────────────────────────────────────────────────

const noChangesWarning = ref(false)

const hasChanges = computed(() => {
    if (props.isDraftMode) return true
    if (!props.isEditMode || !props.initialData) return true
    const tracked = [
        'display_name',
        'gender',
        'birth_date',
        'birth_accuracy',
        'death_date',
        'death_accuracy',
        'family_unit_id',
    ]
    return tracked.some((field) => {
        let original = props.initialData[field] ?? ''
        if (field === 'birth_date') original = initBirthDate
        else if (field === 'death_date') original = initDeathDate
        return String(original) !== String(form[field] ?? '')
    })
})

// ── AUTO-SAVE ─────────────────────────────────────────────────────────────────

watch(
    () => ({
        display_name: form.display_name,
        gender: form.gender,
        birth_date: form.birth_date,
        birth_accuracy: form.birth_accuracy,
        death_date: form.death_date,
        death_accuracy: form.death_accuracy,
        family_unit_id: form.family_unit_id,
    }),
    () => {
        if (!props.isEditMode) scheduleSave()
    },
    { deep: true },
)

// ── NAVIGATION ────────────────────────────────────────────────────────────────

const handleNext = () => {
    // nextStep() dari usePersonForm memvalidasi currentStep.value yang merupakan ID asli STEPS
    // Kita inject actual step ID agar validasi sesuai step yang benar-benar aktif
    if (validateStep(actualStepId.value)) {
        currentStep.value++
    }
}
const handlePrev = () => {
    if (currentStep.value > 1) currentStep.value--
}

// ── SUBMIT ────────────────────────────────────────────────────────────────────

const submitAsSubmit = () => {
    if (props.isEditMode && !props.isDraftMode && !hasChanges.value) {
        noChangesWarning.value = true
        return
    }
    noChangesWarning.value = false
    if (!validateStep(actualStepId.value)) return
    form.is_draft = false
    submitForm()
}

const submitAsDraft = () => {
    form.is_draft = true
    submitForm()
}

const submitForm = () => {
    const method = props.submitMethod === 'patch' ? 'patch' : 'post'
    form[method](props.submitRoute, {
        onSuccess: () => {
            clearDraft()
            emit('submitted')
        },
    })
}

// ── UNLOAD GUARD ──────────────────────────────────────────────────────────────

const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value) {
        e.preventDefault()
        e.returnValue = ''
    }
}
onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload))
onBeforeUnmount(() =>
    window.removeEventListener('beforeunload', handleBeforeUnload),
)

// ── LABELS ────────────────────────────────────────────────────────────────────

const submitLabel = computed(() => {
    if (props.isDraftMode)
        return isAdmin.value ? 'Simpan & Aktifkan' : 'Ajukan ke Admin'
    if (props.isEditMode)
        return isAdmin.value ? 'Simpan Perubahan' : 'Ajukan Perubahan'
    return isAdmin.value ? 'Tambah Anggota' : 'Ajukan Permohonan'
})

const showDraftButton = computed(
    () =>
        currentStep.value === totalSteps.value &&
        (!props.isEditMode || props.isDraftMode),
)
</script>

<template>
    <div class="mx-auto max-w-2xl">
        <!-- Draft restore banner -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div
                v-if="showDraftRestore"
                class="mb-6 flex items-center gap-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm"
            >
                <div class="flex-shrink-0 text-2xl">💾</div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-indigo-800">
                        Draft ditemukan!
                    </p>
                    <p class="mt-0.5 text-xs text-indigo-600">
                        Tersimpan pada
                        {{
                            new Date(localDraft?.saved_at).toLocaleString(
                                'id-ID',
                            )
                        }}
                    </p>
                </div>
                <div class="flex flex-shrink-0 gap-2">
                    <button
                        @click="restoreLocalDraft"
                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        Lanjutkan
                    </button>
                    <button
                        @click="dismissDraft"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-indigo-600 transition-colors hover:bg-indigo-100"
                    >
                        Abaikan
                    </button>
                </div>
            </div>
        </Transition>

        <!-- No-changes warning -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
        >
            <div
                v-if="noChangesWarning"
                class="mb-4 flex items-start gap-3 rounded-xl border border-yellow-300 bg-yellow-50 p-4"
            >
                <span class="flex-shrink-0 text-lg text-yellow-500">⚠️</span>
                <div>
                    <p class="text-sm font-semibold text-yellow-800">
                        Tidak Ada Perubahan
                    </p>
                    <p class="mt-0.5 text-xs text-yellow-700">
                        Ubah setidaknya satu field sebelum menyimpan.
                    </p>
                </div>
            </div>
        </Transition>

        <!-- Progress steps -->
        <div class="mb-8">
            <div class="relative flex items-center justify-between">
                <div
                    class="absolute left-0 right-0 top-5 z-0 h-0.5 bg-gray-200"
                >
                    <div
                        class="h-full bg-indigo-500 transition-all duration-500 ease-out"
                        :style="`width: ${totalSteps > 1 ? ((currentStep - 1) / (totalSteps - 1)) * 100 : 0}%`"
                    />
                </div>
                <button
                    v-for="step in visibleStepStatus"
                    :key="step.id"
                    @click="goToStep(step.id)"
                    :disabled="step.id > currentStep"
                    :class="[
                        'relative z-10 flex flex-col items-center gap-1.5',
                        step.id > currentStep
                            ? 'cursor-not-allowed'
                            : 'cursor-pointer',
                    ]"
                >
                    <div
                        :class="[
                            'flex h-10 w-10 items-center justify-center rounded-full border-2 text-sm font-bold transition-all',
                            step.current
                                ? 'scale-110 border-indigo-600 bg-indigo-600 text-white shadow-md'
                                : step.completed
                                  ? 'border-indigo-400 bg-indigo-100 text-indigo-700'
                                  : step.hasError
                                    ? 'border-red-400 bg-red-100 text-red-700'
                                    : 'border-gray-300 bg-white text-gray-400',
                        ]"
                    >
                        <svg
                            v-if="step.completed && !step.current"
                            class="h-5 w-5 text-indigo-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <span v-else>{{ step.id }}</span>
                    </div>
                    <span
                        :class="[
                            'whitespace-nowrap text-xs font-medium',
                            step.current ? 'text-indigo-700' : 'text-gray-500',
                        ]"
                        >{{ step.label }}</span
                    >
                </button>
            </div>
        </div>

        <!-- Auto-save indicator -->
        <div
            v-if="!isEditMode"
            class="flex items-center gap-2 border-b border-gray-100 bg-gray-50 px-6 py-2 text-xs text-gray-400"
        >
            <template v-if="isSaving">
                <svg
                    class="h-3 w-3 animate-spin"
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
                Menyimpan...
            </template>
            <template v-else-if="lastSavedAt"
                >✓ Tersimpan otomatis {{ savedAtFormatted }}</template
            >
            <template v-else
                >Draft akan tersimpan otomatis saat Anda mengetik</template
            >
        </div>

        <!-- Card -->
        <div
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <!-- Step content -->
            <div class="p-6 md:p-8">
                <component
                    :is="currentStepComponent"
                    :key="currentStep"
                    :form="form"
                    :errors="allErrors"
                    :family-units="familyUnits"
                    :all-people="allPeople"
                    :gender-label="genderLabel"
                    :accuracy-label="accuracyLabel"
                    :is-edit-mode="isEditMode"
                    :is-admin="isAdmin"
                />
            </div>

            <!-- Footer nav -->
            <div
                class="flex items-center justify-between border-t border-gray-100 bg-gray-50/50 px-6 py-4 md:px-8"
            >
                <button
                    v-if="currentStep > 1"
                    type="button"
                    @click="handlePrev"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    Kembali
                </button>
                <div v-else />

                <div class="flex items-center gap-3">
                    <!-- Simpan Draft -->
                    <button
                        v-if="showDraftButton"
                        type="button"
                        @click="submitAsDraft"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:opacity-50"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
                            />
                        </svg>
                        Simpan Draft
                    </button>

                    <!-- Next -->
                    <button
                        v-if="currentStep < totalSteps"
                        type="button"
                        @click="handleNext"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700"
                    >
                        Lanjut
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>

                    <!-- Submit -->
                    <button
                        v-else
                        type="button"
                        @click="submitAsSubmit"
                        :disabled="form.processing"
                        :class="[
                            'inline-flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors disabled:opacity-60',
                            isDraftMode
                                ? 'bg-green-600 hover:bg-green-700'
                                : 'bg-indigo-600 hover:bg-indigo-700',
                        ]"
                    >
                        <svg
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
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
                        {{ submitLabel }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Server errors -->
        <div
            v-if="
                form.errors &&
                Object.keys(form.errors).length &&
                !Object.values(allErrors).some(Boolean)
            "
            class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4"
        >
            <p class="mb-1 text-sm font-medium text-red-700">
                Terjadi kesalahan:
            </p>
            <ul class="space-y-0.5 text-xs text-red-600">
                <li v-for="(err, key) in form.errors" :key="key">
                    • {{ err }}
                </li>
            </ul>
        </div>
    </div>
</template>
