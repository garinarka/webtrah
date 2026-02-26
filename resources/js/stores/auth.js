import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useAuthStore = defineStore("auth", () => {
    const user = ref(null);

    const isAuthenticated = computed(() => !!user.value);
    const isAdmin = computed(() => user.value?.role === "admin");
    const isModerator = computed(() =>
        ["admin", "moderator"].includes(user.value?.role || ""),
    );
    const isUser = computed(() => user.value?.role === "user");

    const can = (permission) =>
        user.value?.permissions?.includes(permission) || false;
    const canAny = (permissions) => permissions.some((p) => can(p));
    const canAll = (permissions) => permissions.every((p) => can(p));

    const initializeFromPage = (authData) => {
        user.value = authData?.user || null;
    };

    return {
        user,
        isAuthenticated,
        isAdmin,
        isModerator,
        isUser,
        can,
        canAny,
        canAll,
        initializeFromPage,
    };
});
