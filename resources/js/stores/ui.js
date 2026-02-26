import { defineStore } from "pinia";
import { ref, computed } from "vue";

export const useUiStore = defineStore("ui", () => {
    const sidebarOpen = ref(false);
    const isMobile = ref(window.innerWidth < 768);
    const activeModal = ref(null);
    const activeSheet = ref(null);
    const toasts = ref([]);

    const toggleSidebar = () => {
        sidebarOpen.value = !sidebarOpen.value;
    };
    const openModal = (id) => {
        activeModal.value = id;
    };
    const closeModal = () => {
        activeModal.value = null;
    };
    const openSheet = (id) => {
        activeSheet.value = id;
    };
    const closeSheet = () => {
        activeSheet.value = null;
    };

    const addToast = (message, type = "info") => {
        const id = Math.random().toString(36).substring(7);
        toasts.value.push({ id, message, type });
        setTimeout(() => {
            toasts.value = toasts.value.filter((t) => t.id !== id);
        }, 5000);
    };

    const updateIsMobile = () => {
        isMobile.value = window.innerWidth < 768;
    };

    return {
        sidebarOpen,
        isMobile,
        activeModal,
        activeSheet,
        toasts,
        toggleSidebar,
        openModal,
        closeModal,
        openSheet,
        closeSheet,
        addToast,
        updateIsMobile,
    };
});
