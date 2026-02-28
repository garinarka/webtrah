import { ref, computed } from "vue";
import axios from "axios";

const DEBOUNCE_MS = 800;

export function useDuplicateDetection() {
    const result = ref(null);
    const isChecking = ref(false);
    const checkError = ref(null);
    const isDismissed = ref(false);

    let debounceTimer = null;
    let lastChecked = {};

    const check = async ({
        display_name,
        birth_date,
        gender,
        family_unit_id,
        exclude_id,
    } = {}) => {
        if (!display_name || display_name.trim().length < 2) {
            result.value = null;
            return;
        }

        // skip if same as last check
        const key = JSON.stringify({
            display_name,
            birth_date,
            gender,
            family_unit_id,
            exclude_id,
        });
        if (key === JSON.stringify(lastChecked)) return;
        lastChecked = {
            display_name,
            birth_date,
            gender,
            family_unit_id,
            exclude_id,
        };

        isDismissed.value = false;
        isChecking.value = true;
        checkError.value = null;

        try {
            const { data } = await axios.post("/people/check-duplicates", {
                display_name,
                birth_date,
                gender,
                family_unit_id,
                exclude_id,
            });
            result.value = data;
        } catch (err) {
            checkError.value = "Gagal memeriksa duplikat.";
            result.value = null;
        } finally {
            isChecking.value = false;
        }
    };

    const scheduleCheck = (params) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => check(params), DEBOUNCE_MS);
    };

    const dismiss = () => {
        isDismissed.value = true;
    };
    const reset = () => {
        result.value = null;
        isDismissed.value = false;
        lastChecked = {};
    };

    // ── COMPUTED HELPERS ───────────────────────────────────────────────────

    const risk = computed(() => result.value?.risk ?? "none");

    const hasWarning = computed(
        () =>
            !isDismissed.value && result.value && result.value.risk !== "none",
    );

    const riskConfig = computed(
        () =>
            ({
                none: {
                    color: "green",
                    label: "Tidak ada duplikat",
                    icon: "✓",
                    bg: "bg-green-50 border-green-200",
                    text: "text-green-700",
                },
                low: {
                    color: "yellow",
                    label: "Nama serupa ditemukan",
                    icon: "⚠",
                    bg: "bg-yellow-50 border-yellow-200",
                    text: "text-yellow-700",
                },
                medium: {
                    color: "orange",
                    label: "Kemungkinan duplikat",
                    icon: "⚠",
                    bg: "bg-orange-50 border-orange-200",
                    text: "text-orange-700",
                },
                high: {
                    color: "red",
                    label: "Kemungkinan besar duplikat!",
                    icon: "✕",
                    bg: "bg-red-50 border-red-200",
                    text: "text-red-700",
                },
            })[risk.value] ?? {},
    );

    const allMatches = computed(() => {
        if (!result.value) return [];
        return [
            ...(result.value.exact_matches ?? []),
            ...(result.value.birth_date_matches ?? []),
            ...(result.value.similar_matches ?? []),
        ].reduce((acc, cur) => {
            if (!acc.find((x) => x.id === cur.id)) acc.push(cur);
            return acc;
        }, []);
    });

    return {
        result,
        isChecking,
        checkError,
        isDismissed,
        risk,
        hasWarning,
        riskConfig,
        allMatches,
        scheduleCheck,
        check,
        dismiss,
        reset,
    };
}
