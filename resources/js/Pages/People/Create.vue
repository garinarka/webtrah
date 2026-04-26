<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PersonFormWizard from '@/features/people/components/PersonFormWizard.vue'

const props = defineProps({
    familyUnits: { type: Array, default: () => [] },
    allPeople: { type: Array, default: () => [] },
    savedDraft: { type: Object, default: null },
    defaultFamilyUnitId: { type: String, default: null },
    // Kalau dibuka dari /family/{id} — unit dikunci, step 3 di-skip
    lockedFamilyUnitId: { type: String, default: null },
    lockedFamilyUnit: { type: Object, default: null },
})

// Kalau ada lockedFamilyUnitId, filter step jadi hanya 3 step (tanpa "Keluarga")
const stepsOverride = computed(() =>
    props.lockedFamilyUnitId ? ['basic', 'dates', 'review'] : null,
)
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
                    <template v-if="lockedFamilyUnit">
                        <span class="text-gray-300">/</span>
                        <Link
                            href="/family"
                            class="transition-colors hover:text-indigo-600"
                            >Unit Keluarga</Link
                        >
                        <span class="text-gray-300">/</span>
                        <Link
                            :href="`/family/${lockedFamilyUnit.id}`"
                            class="transition-colors hover:text-indigo-600"
                        >
                            {{ lockedFamilyUnit.name }}
                        </Link>
                    </template>
                    <span class="text-gray-300">/</span>
                    <span class="font-medium text-gray-900"
                        >Tambah Anggota</span
                    >
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">
                    Tambah Anggota Baru
                </h1>
                <p v-if="lockedFamilyUnit" class="mt-1 text-sm text-gray-500">
                    Anggota akan langsung terdaftar di unit keluarga
                    <span class="font-medium text-indigo-700">{{
                        lockedFamilyUnit.name
                    }}</span
                    >.
                </p>
                <p v-else class="mt-1 text-sm text-gray-500">
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
                :locked-family-unit-id="lockedFamilyUnitId"
                submit-route="/people"
                submit-method="post"
            />
        </div>
    </AppLayout>
</template>
