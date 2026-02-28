<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from './AppLayout.vue';
import ApprovalQueueWidget from '@/features/approvals/components/ApprovalQueueWidget.vue';
</script>

<template>
    <AppLayout>
        <template #sidebar-extra>
            <div class="mt-6 px-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin Tools</h3>
                <ApprovalQueueWidget class="mt-2" />
                <nav class="mt-4 space-y-1">
                    <a href="/approvals"
                        class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                        Queue Persetujuan
                    </a>
                    <a href="/people"
                        class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                        Kelola Anggota
                    </a>
                </nav>
            </div>
        </template>
        <slot />
    </AppLayout>
</template>
