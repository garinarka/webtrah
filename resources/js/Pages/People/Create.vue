<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PersonFormWizard from '@/features/people/components/PersonFormWizard.vue';

const props = defineProps({
    familyUnits: { type: Array, default: () => [] },
    savedDraft: { type: Object, default: null },
    defaultFamilyUnitId: { type: String, default: null },
});

const initialData = props.savedDraft ?? {
    family_unit_id: props.defaultFamilyUnitId,
};
</script>

<template>

    <Head title="Tambah Anggota Baru" />
    <AppLayout>
        <div class="max-w-2xl mx-auto">
            <!-- page header -->
            <div class="mb-8">
                <nav class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                    <Link href="/people" class="hover:text-indigo-600 transition-colors">Anggota Keluarga</Link>
                    <span class="text-gray-300">/</span>
                    <span class="text-gray-900 font-medium">Tambah Baru</span>
                </nav>
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Tambah Anggota Keluarga</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Isi data anggota baru. Data akan ditinjau sebelum dipublikasikan.
                        </p>
                    </div>
                    <Link href="/people"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Batal
                    </Link>
                </div>
            </div>

            <PersonFormWizard :initial-data="initialData" :family-units="familyUnits" :is-edit-mode="false"
                submit-route="/people" submit-method="post" />
        </div>
    </AppLayout>
</template>
