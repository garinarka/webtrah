<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PersonFormWizard from '@/features/people/components/PersonFormWizard.vue';

const props = defineProps({
    person: { type: Object, required: true },
    familyUnits: { type: Array, default: () => [] },
    auditLogs: { type: Array, default: () => [] },
});

const eventLabel = (event) => ({
    created: '✅ Dibuat',
    updated: '✏️ Diperbarui',
    draft_saved: '💾 Draft Tersimpan',
    delete_requested: '🗑 Hapus Diminta',
    update_requested: '📋 Ubah Diminta',
    deleted: '❌ Dihapus',
}[event] ?? event);

const eventColor = (event) => ({
    created: 'text-green-700 bg-green-50',
    updated: 'text-blue-700 bg-blue-50',
    draft_saved: 'text-gray-600 bg-gray-50',
    delete_requested: 'text-red-700 bg-red-50',
    update_requested: 'text-amber-700 bg-amber-50',
    deleted: 'text-red-800 bg-red-100',
}[event] ?? 'text-gray-600 bg-gray-50');
</script>

<template>

    <Head :title="`Edit: ${person.display_name}`" />
    <AppLayout>
        <div class="max-w-5xl mx-auto">
            <!-- page header -->
            <div class="mb-8">
                <nav class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                    <Link href="/people" class="hover:text-indigo-600 transition-colors">Daftar Anggota</Link>
                    <span class="text-gray-300">/</span>
                    <Link :href="`/people/${person.id}`" class="hover:text-indigo-600 transition-colors">
                        {{ person.display_name }}
                    </Link>
                    <span class="text-gray-300">/</span>
                    <span class="text-gray-900 font-medium">Edit</span>
                </nav>
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Edit Anggota</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Perubahan data memerlukan persetujuan moderator / admin.
                        </p>
                    </div>
                    <span :class="[
                        'px-3 py-1 text-xs font-semibold rounded-full',
                        person.status === 'active' ? 'bg-green-100 text-green-700' :
                            person.status === 'pending' ? 'bg-amber-100 text-amber-700' :
                                person.status === 'draft' ? 'bg-gray-100 text-gray-600' :
                                    'bg-red-100 text-red-700',
                    ]">
                        {{ person.status }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- main form (2/3) -->
                <div class="lg:col-span-2">
                    <PersonFormWizard :initial-data="{
                        ...person,
                        birth_date: person.birth_date,
                        death_date: person.death_date,
                    }" :family-units="familyUnits" :is-edit-mode="true" :person-id="person.id"
                        :submit-route="`/people/${person.id}`" submit-method="patch" />
                </div>

                <!-- audit log sidebar (1/3) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Riwayat Perubahan
                        </h3>

                        <div v-if="auditLogs.length === 0" class="text-xs text-gray-400 text-center py-6">
                            Belum ada riwayat perubahan.
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="log in auditLogs" :key="log.id"
                                class="flex gap-3 pb-3 border-b border-gray-50 last:border-0 last:pb-0">
                                <!-- event badge -->
                                <div class="flex-shrink-0 mt-0.5">
                                    <span :class="['px-1.5 py-0.5 text-xs font-medium rounded', eventColor(log.event)]">
                                        {{ eventLabel(log.event) }}
                                    </span>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500">
                                        oleh <span class="font-medium text-gray-700">{{ log.user?.name ?? 'System'
                                            }}</span>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ new Date(log.created_at).toLocaleString('id-ID', {
                                            day: '2-digit', month: 'short', year: 'numeric',
                                            hour: '2-digit', minute: '2-digit'
                                        }) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
