import { ref, watch } from "vue";
import axios from "axios";

const STORAGE_KEY = "webtrah_person_draft";
const DEBOUNCE_MS = 1500;

export function useAutoSave(form, currentStep) {
    const lastSavedAt = ref(null);
    const isSaving = ref(false);
    const saveError = ref(null);
    const hasUnsavedChanges = ref(false);

    let debounceTimer = null;

    // ── SAVE TO LOCALSTORAGE (instant) ─────────────────────────────────────

    const saveToLocal = () => {
        try {
            const payload = {
                ...(form.data?.() ?? form),
                current_step: currentStep.value,
                saved_at: new Date().toISOString(),
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
        } catch (e) {
            console.warn("LocalStorage save failed:", e);
        }
    };

    const loadFromLocal = () => {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    };

    const clearLocal = () => {
        localStorage.removeItem(STORAGE_KEY);
    };

    // ── SAVE TO SERVER ─────────────────────────────────────────────────────

    const saveToServer = async () => {
        if (isSaving.value) return;

        isSaving.value = true;
        saveError.value = null;

        try {
            const payload = {
                ...(form.data?.() ?? form),
                current_step: currentStep.value,
            };

            const { data } = await axios.post("/people/draft/save", payload);

            lastSavedAt.value = data.saved_at;
            hasUnsavedChanges.value = false;
        } catch (err) {
            saveError.value = "Gagal menyimpan draft ke server.";
            console.error("Auto-save error:", err);
        } finally {
            isSaving.value = false;
        }
    };

    // ── DEBOUNCED SAVE (both layers) ───────────────────────────────────────

    const scheduleSave = () => {
        hasUnsavedChanges.value = true;
        saveToLocal(); // instant local

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            saveToServer();
        }, DEBOUNCE_MS);
    };

    const saveNow = async () => {
        clearTimeout(debounceTimer);
        saveToLocal();
        await saveToServer();
    };

    // ── CLEAR DRAFT ────────────────────────────────────────────────────────

    const clearDraft = async () => {
        clearLocal();
        clearTimeout(debounceTimer);
        hasUnsavedChanges.value = false;
        lastSavedAt.value = null;

        try {
            await axios.delete("/people/draft/clear");
        } catch {
            // non-critical
        }
    };

    // ── FORMATTED SAVE TIME ────────────────────────────────────────────────

    const savedAtFormatted = () => {
        if (!lastSavedAt.value) return null;
        const d = new Date(lastSavedAt.value);
        return d.toLocaleTimeString("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
    };

    return {
        lastSavedAt,
        isSaving,
        saveError,
        hasUnsavedChanges,
        scheduleSave,
        saveNow,
        clearDraft,
        loadFromLocal,
        clearLocal,
        savedAtFormatted,
        STORAGE_KEY,
    };
}
