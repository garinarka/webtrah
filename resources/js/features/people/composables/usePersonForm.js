import { reactive, computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const STEPS = [
    { id: 1, key: 'basic',  label: 'Identitas',   fields: ['display_name', 'gender'] },
    { id: 2, key: 'dates',  label: 'Tanggal',     fields: ['birth_date', 'birth_accuracy', 'death_date', 'death_accuracy'] },
    { id: 3, key: 'family', label: 'Keluarga',    fields: ['family_unit_id'] },
    { id: 4, key: 'review', label: 'Tinjau',      fields: [] },
];

export function usePersonForm(initialData = {}) {
    const currentStep = ref(initialData.current_step || 1);
    const isSubmitting = ref(false);

    const form = useForm({
        display_name:    initialData.display_name    || '',
        gender:          initialData.gender          || '',
        birth_date:      initialData.birth_date      || '',
        birth_accuracy:  initialData.birth_accuracy  || 'unknown',
        death_date:      initialData.death_date      || '',
        death_accuracy:  initialData.death_accuracy  || 'unknown',
        family_unit_id:  initialData.family_unit_id  || '',
        is_draft:        false,
    });

    // ── CLIENT-SIDE VALIDATION ──────────────────────────────────────────────

    const clientErrors = reactive({});

    const validateField = (field, value = form[field]) => {
        delete clientErrors[field];

        if (field === 'display_name') {
            if (!value?.trim()) {
                clientErrors[field] = 'Nama wajib diisi.';
            } else if (value.trim().length < 2) {
                clientErrors[field] = 'Nama minimal 2 karakter.';
            } else if (value.trim().length > 255) {
                clientErrors[field] = 'Nama maksimal 255 karakter.';
            }
        }

        if (field === 'gender') {
            if (!value) clientErrors[field] = 'Jenis kelamin wajib dipilih.';
        }

        if (field === 'birth_date' && value) {
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                clientErrors[field] = 'Format tanggal tidak valid.';
            } else if (date > new Date()) {
                clientErrors[field] = 'Tanggal lahir tidak boleh di masa depan.';
            }
        }

        if (field === 'death_date' && value) {
            const death = new Date(value);
            if (isNaN(death.getTime())) {
                clientErrors[field] = 'Format tanggal tidak valid.';
            } else if (death > new Date()) {
                clientErrors[field] = 'Tanggal meninggal tidak boleh di masa depan.';
            } else if (form.birth_date && death < new Date(form.birth_date)) {
                clientErrors[field] = 'Tanggal meninggal harus setelah tanggal lahir.';
            }
        }

        if (field === 'family_unit_id') {
            if (!value) clientErrors[field] = 'Unit keluarga wajib dipilih.';
        }
    };

    const validateStep = (stepId) => {
        const step = STEPS.find(s => s.id === stepId);
        if (!step) return true;

        step.fields.forEach(field => validateField(field));

        const stepErrors = step.fields.filter(f => clientErrors[f]);
        return stepErrors.length === 0;
    };

    const allErrors = computed(() => ({
        ...clientErrors,
        ...form.errors,
    }));

    // ── STEP NAVIGATION ────────────────────────────────────────────────────

    const canGoNext = computed(() => currentStep.value < STEPS.length);
    const canGoPrev = computed(() => currentStep.value > 1);

    const nextStep = () => {
        if (!validateStep(currentStep.value)) return false;
        if (canGoNext.value) currentStep.value++;
        return true;
    };

    const prevStep = () => {
        if (canGoPrev.value) currentStep.value--;
    };

    const goToStep = (step) => {
        // Only allow going back, or to already-valid steps
        if (step < currentStep.value) {
            currentStep.value = step;
        }
    };

    // ── STEP COMPLETION STATUS ─────────────────────────────────────────────

    const stepStatus = computed(() => {
        return STEPS.map(step => {
            const hasAllRequired = step.fields.every(f => {
                if (f === 'birth_date' || f === 'death_date') return true; // optional
                return !!form[f];
            });
            const hasErrors = step.fields.some(f => allErrors.value[f]);
            return {
                ...step,
                completed: hasAllRequired && !hasErrors && step.id < currentStep.value,
                current:   step.id === currentStep.value,
                hasError:  hasErrors,
            };
        });
    });

    // ── COMPUTED DISPLAY HELPERS ───────────────────────────────────────────

    const genderLabel = computed(() => ({
        male: 'Laki-laki', female: 'Perempuan', unknown: 'Tidak Diketahui',
    }[form.gender] || '-'));

    const accuracyLabel = (val) => ({
        exact: 'Tepat', year_month: 'Bulan & Tahun',
        year: 'Tahun Saja', unknown: 'Tidak Diketahui',
    }[val] || val);

    const isDeceased = computed(() => !!form.death_date);

    return {
        form,
        currentStep,
        steps: STEPS,
        stepStatus,
        clientErrors,
        allErrors,
        isSubmitting,
        canGoNext,
        canGoPrev,
        nextStep,
        prevStep,
        goToStep,
        validateField,
        validateStep,
        genderLabel,
        accuracyLabel,
        isDeceased,
    };
}
