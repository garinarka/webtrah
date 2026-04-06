<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PersonFormWizard from '@/features/people/components/PersonFormWizard.vue'

defineProps({
    familyUnits: { type: Array, default: () => [] },
    allPeople: { type: Array, default: () => [] },
    savedDraft: { type: Object, default: null },
    defaultFamilyUnitId: { type: String, default: null },
})
</script>

<template>
    <Head title="Tambah Anggota" />
    <AppLayout>
        <div class="mx-auto max-w-2xl">
            <div class="mb-8">
                <nav class="mb-3 flex items-center gap-2 text-sm text-gray-500">
                    <Link
                        href="/people"
                        class="transition-colors hover:text-indigo-600"
                        >Daftar Anggota</Link
                    >
                    <span class="text-gray-300">/</span>
                    <span class="font-medium text-gray-900"
                        >Tambah Anggota</span
                    >
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">
                    Tambah Anggota Baru
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Isi data anggota keluarga baru secara bertahap.
                </p>
            </div>

            <PersonFormWizard
                :initial-data="
                    savedDraft ?? { family_unit_id: defaultFamilyUnitId }
                "
                :family-units="familyUnits"
                :all-people="allPeople"
                :is-edit-mode="false"
                submit-route="/people"
                submit-method="post"
            />
        </div>
    </AppLayout>
</template>
