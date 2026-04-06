<script setup>
import FamilyUnitsForm from './Form.vue'
defineProps({
    familyUnit: { type: Object, required: true },
    moderators: { type: Array, default: () => [] },
})
</script>
<template>
    <FamilyUnitsForm :family-unit="familyUnit" :moderators="moderators" />
</template>
