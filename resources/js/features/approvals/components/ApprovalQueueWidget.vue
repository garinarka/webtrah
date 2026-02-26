<script setup>
defineProps({
    count: { type: Number, default: 3 }
});
</script>

<template>
    <div class="p-3 bg-amber-50 rounded-md border border-amber-200">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-amber-800">Pending Approval</span>
            <span class="bg-amber-100 text-amber-600 py-0.5 px-2 rounded-full text-xs font-bold">{{ count }}</span>
        </div>
        <a href="/approvals" class="mt-2 text-xs text-amber-700 hover:text-amber-900 underline block">
            Lihat queue →
        </a>
    </div>
</template>
