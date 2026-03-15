import { reactive, computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

const STEPS = [
    {
        id: 1,
        key: "basic",
        label: "Identitas",
        fields: ["display_name", "gender"],
    },
    {
        id: 2,
        key: "dates",
        label: "Tanggal",
        fields: [
            "birth_date",
            "birth_accuracy",
            "death_date",
            "death_accuracy",
        ],
    },
    { id: 3, key: "family", label: "Keluarga", fields: ["family_unit_id"] },
    { id: 4, key: "review", label: "Tinjau", fields: [] },
];

/**
 * Format tanggal ISO → format sesuai input type.
 * Parse komponen string langsung agar tidak kena timezone shift.
 * new Date("2007-09-15") = UTC midnight → UTC+7 = 14 Sep.
 * Dengan split('-'), kita baca tahun/bulan/hari sebagai angka lokal.
 */
function formatDateForInput(dateStr, accuracy) {
    if (!dateStr) return "";
    const raw = String(dateStr).split("T")[0]; // strip timestamp jika ada
    const parts = raw.split("-");
    if (!parts[0]) return "";
    const year = parts[0];
    const month = parts[1] ?? "01";
    const day = parts[2] ?? "01";
    if (accuracy === "year") return year;
    if (accuracy === "year_month") return `${year}-${month}`;
    return `${year}-${month}-${day}`;
}

export function usePersonForm(initialData = {}) {
    const isEditMode = initialData.isEditMode ?? false;

    /**
     * isAdmin bisa berupa:
     * - boolean  (cara lama — frozen di setup time, bisa salah kalau page.props belum ready)
     * - function/getter  (cara baru — di-evaluate tiap kali, selalu up-to-date)
     */
    const resolveIsAdmin = () => {
        const raw = initialData.isAdmin;
        return typeof raw === "function" ? raw() : (raw ?? false);
    };

    const currentStep = ref(initialData.current_step || 1);
    const isSubmitting = ref(false);

    const initBirthDate = formatDateForInput(
        initialData.birth_date,
        initialData.birth_accuracy || "unknown",
    );
    const initDeathDate = formatDateForInput(
        initialData.death_date,
        initialData.death_accuracy || "unknown",
    );

    const form = useForm({
        display_name: initialData.display_name || "",
        gender: initialData.gender || "",
        birth_date: initBirthDate,
        birth_accuracy: initialData.birth_accuracy || "unknown",
        death_date: initDeathDate,
        death_accuracy: initialData.death_accuracy || "unknown",
        family_unit_id: initialData.family_unit_id || "",
        is_draft: false,
        edit_reason: initialData.edit_reason || "",
    });

    // ── VALIDATION ─────────────────────────────────────────────────────────

    const clientErrors = reactive({});

    const validateField = (field, value = form[field]) => {
        delete clientErrors[field];

        if (field === "display_name") {
            if (!value?.trim()) clientErrors[field] = "Nama wajib diisi.";
            else if (value.trim().length < 2)
                clientErrors[field] = "Nama minimal 2 karakter.";
            else if (value.trim().length > 255)
                clientErrors[field] = "Nama maksimal 255 karakter.";
        }

        if (field === "gender") {
            if (!value) clientErrors[field] = "Jenis kelamin wajib dipilih.";
        }

        if (field === "birth_date" && value) {
            if (form.birth_accuracy === "year") {
                const y = parseInt(value);
                if (isNaN(y) || y < 1800 || y > new Date().getFullYear())
                    clientErrors[field] = "Tahun lahir tidak valid.";
            } else if (form.birth_accuracy !== "unknown") {
                const parts = value.split("-").map(Number);
                const d = new Date(
                    parts[0],
                    (parts[1] ?? 1) - 1,
                    parts[2] ?? 1,
                );
                if (isNaN(d.getTime()))
                    clientErrors[field] = "Format tanggal tidak valid.";
                else if (d > new Date())
                    clientErrors[field] =
                        "Tanggal lahir tidak boleh di masa depan.";
            }
        }

        if (field === "death_date" && value) {
            const parts = value.split("-").map(Number);
            const death = new Date(
                parts[0],
                (parts[1] ?? 1) - 1,
                parts[2] ?? 1,
            );
            if (isNaN(death.getTime()))
                clientErrors[field] = "Format tanggal tidak valid.";
            else if (death > new Date())
                clientErrors[field] =
                    "Tanggal meninggal tidak boleh di masa depan.";
            else if (form.birth_date) {
                const bp = form.birth_date.split("-").map(Number);
                const birth = new Date(bp[0], (bp[1] ?? 1) - 1, bp[2] ?? 1);
                if (death < birth)
                    clientErrors[field] =
                        "Tanggal meninggal harus setelah tanggal lahir.";
            }
        }

        if (field === "family_unit_id") {
            if (!value) clientErrors[field] = "Unit keluarga wajib dipilih.";
        }

        // edit_reason hanya wajib untuk non-admin saat edit mode
        if (field === "edit_reason" && isEditMode && !resolveIsAdmin()) {
            if (!value?.trim())
                clientErrors[field] = "Alasan perubahan wajib diisi.";
            else if (value.trim().length < 5)
                clientErrors[field] = "Alasan minimal 5 karakter.";
        }
    };

    const validateStep = (stepId) => {
        const step = STEPS.find((s) => s.id === stepId);
        if (!step) return true;

        step.fields.forEach((field) => validateField(field));

        // Step 4: cek edit_reason hanya untuk non-admin saat edit
        if (stepId === 4 && isEditMode && !resolveIsAdmin()) {
            validateField("edit_reason");
            return (
                !step.fields.some((f) => clientErrors[f]) &&
                !clientErrors["edit_reason"]
            );
        }

        return !step.fields.some((f) => clientErrors[f]);
    };

    const allErrors = computed(() => ({
        ...clientErrors,
        ...form.errors,
    }));

    // ── NAVIGATION ─────────────────────────────────────────────────────────

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
        if (step < currentStep.value) currentStep.value = step;
    };

    // ── STEP STATUS ────────────────────────────────────────────────────────

    const stepStatus = computed(() =>
        STEPS.map((step) => {
            const hasAllRequired = step.fields.every((f) => {
                if (f === "birth_date" || f === "death_date") return true;
                return !!form[f];
            });
            const hasErrors = step.fields.some((f) => allErrors.value[f]);
            const hasEditReasonError =
                step.id === 4 &&
                isEditMode &&
                !resolveIsAdmin() &&
                !!allErrors.value["edit_reason"];
            return {
                ...step,
                completed:
                    hasAllRequired &&
                    !hasErrors &&
                    !hasEditReasonError &&
                    step.id < currentStep.value,
                current: step.id === currentStep.value,
                hasError: hasErrors || hasEditReasonError,
            };
        }),
    );

    // ── HELPERS ────────────────────────────────────────────────────────────

    const genderLabel = computed(
        () =>
            ({
                male: "Laki-laki",
                female: "Perempuan",
                unknown: "Tidak Diketahui",
            })[form.gender] || "-",
    );

    const accuracyLabel = (val) =>
        ({
            exact: "Tepat",
            year_month: "Bulan & Tahun",
            year: "Tahun Saja",
            unknown: "Tidak Diketahui",
        })[val] || val;

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
        initBirthDate,
        initDeathDate,
    };
}
