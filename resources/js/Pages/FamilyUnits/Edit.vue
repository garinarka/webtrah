<script setup>
import FamilyUnitsForm from './Form.vue'
defineProps({
    familyUnit: { type: Object, required: true },
    moderators: { type: Array, default: () => [] },
    currentModeratorIds: { type: Array, default: () => [] }, // wajib diforward!
})
</script>
<template>
    <FamilyUnitsForm
        :family-unit="familyUnit"
        :moderators="moderators"
        :current-moderator-ids="currentModeratorIds"
    />
</template>
