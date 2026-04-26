import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)

    const isAuthenticated = computed(() => !!user.value)
    const isAdmin = computed(() => user.value?.role === 'admin')
    // isModerator = true untuk admin juga agar akses moderator-level tetap ada
    const isModerator = computed(() =>
        ['admin', 'moderator'].includes(user.value?.role || ''),
    )
    const isUser = computed(() => user.value?.role === 'user')

    /**
     * IDs unit keluarga yang dikelola moderator (array, max 3).
     * null untuk admin (semua) dan user (tidak ada).
     */
    const managedFamilyUnitIds = computed(
        () => user.value?.managedFamilyUnitIds ?? null,
    )

    /**
     * Apakah user punya wewenang atas unit keluarga tertentu?
     * Admin: selalu true.
     * Moderator: hanya kalau unit ada di managedFamilyUnitIds.
     */
    const canManageUnit = (familyUnitId) => {
        if (isAdmin.value) return true
        if (isModerator.value && managedFamilyUnitIds.value) {
            return managedFamilyUnitIds.value.includes(familyUnitId)
        }
        return false
    }

    const can = (permission) =>
        user.value?.permissions?.includes(permission) || false
    const canAny = (permissions) => permissions.some((p) => can(p))
    const canAll = (permissions) => permissions.every((p) => can(p))

    const initializeFromPage = (authData) => {
        user.value = authData?.user || null
    }

    return {
        user,
        isAuthenticated,
        isAdmin,
        isModerator,
        isUser,
        managedFamilyUnitIds,
        canManageUnit,
        can,
        canAny,
        canAll,
        initializeFromPage,
    }
})
