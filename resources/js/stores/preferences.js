import { defineStore } from "pinia";
import { ref, watch, computed } from "vue";

export const usePreferencesStore = defineStore("preferences", () => {
    const seniorMode = ref(false);
    const highContrast = ref(false);
    const reducedMotion = ref(false);
    const treeViewMode = ref("radial");
    const fontSize = ref("normal");

    const loadFromStorage = () => {
        if (typeof window === "undefined") return;
        const stored = localStorage.getItem("family-tree-preferences");
        if (stored) {
            try {
                const parsed = JSON.parse(stored);
                seniorMode.value = parsed.seniorMode ?? false;
                highContrast.value = parsed.highContrast ?? false;
                reducedMotion.value = parsed.reducedMotion ?? false;
                treeViewMode.value = parsed.treeViewMode ?? "radial";
                fontSize.value = parsed.fontSize ?? "normal";
            } catch (e) {
                console.error("Failed to parse preferences", e);
            }
        }
    };

    watch(
        [seniorMode, highContrast, reducedMotion, treeViewMode, fontSize],
        () => {
            if (typeof window === "undefined") return;
            localStorage.setItem(
                "family-tree-preferences",
                JSON.stringify({
                    seniorMode: seniorMode.value,
                    highContrast: highContrast.value,
                    reducedMotion: reducedMotion.value,
                    treeViewMode: treeViewMode.value,
                    fontSize: fontSize.value,
                }),
            );
        },
        { deep: true },
    );

    const bodyClasses = computed(() => ({
        "senior-mode": seniorMode.value,
        "high-contrast": highContrast.value,
        "reduced-motion": reducedMotion.value,
        [`font-size-${fontSize.value}`]: true,
    }));

    return {
        seniorMode,
        highContrast,
        reducedMotion,
        treeViewMode,
        fontSize,
        loadFromStorage,
        bodyClasses,
    };
});
