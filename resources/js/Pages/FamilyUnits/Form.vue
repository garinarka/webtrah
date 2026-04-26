<script setup>
// Dipakai untuk Create DAN Edit — bedanya hanya props yang diterima
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    familyUnit: { type: Object, default: null }, // null = create mode
    moderators: { type: Array, default: () => [] },
    currentModeratorIds: { type: Array, default: () => [] }, // IDs moderator yang sudah dipilih
})

const isEdit = !!props.familyUnit

// ID user yang sedang login — moderator tidak bisa memilih dirinya sendiri
const page = usePage()
const currentUserId = computed(() => page.props.auth?.user?.id)

// Helper: apakah moderator ini di-disable di daftar pilihan?
const isModeratorDisabled = (mod) => {
    // Di-disable kalau: (1) sudah max 3 dan belum dipilih, ATAU (2) ini adalah akun diri sendiri
    const maxReached =
        !form.moderator_ids.includes(mod.id) && form.moderator_ids.length >= 3
    const isSelf = String(mod.id) === String(currentUserId.value)
    return maxReached || isSelf
}

// Ambil IDs moderator saat ini dari relasi pivot (dikirim via prop moderatorIds)
const form = useForm({
    name: props.familyUnit?.name ?? '',
    description: props.familyUnit?.description ?? '',
    moderator_ids: props.currentModeratorIds ?? [], // array, max 3
})

// Deteksi perubahan saat edit mode
const noChangesWarning = ref(false)

const hasChanges = computed(() => {
    if (!isEdit) return true
    const origIds = [...(props.currentModeratorIds ?? [])].sort().join(',')
    const formIds = [...(form.moderator_ids ?? [])].sort().join(',')
    return (
        form.name !== (props.familyUnit?.name ?? '') ||
        form.description !== (props.familyUnit?.description ?? '') ||
        formIds !== origIds
    )
})

const submit = () => {
    if (isEdit && !hasChanges.value) {
        noChangesWarning.value = true
        return
    }
    noChangesWarning.value = false

    if (isEdit) {
        form.patch(`/family/${props.familyUnit.id}`, {
            onSuccess: () => {},
        })
    } else {
        form.post('/family', {
            onSuccess: () => {},
        })
    }
}
</script>

<template>
    <Head :title="isEdit ? `Edit: ${familyUnit.name}` : 'Buat Unit Keluarga'" />
    <AppLayout>
        <div class="mx-auto max-w-xl">
            <!-- Breadcrumb -->
            <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
                <Link
                    href="/family"
                    class="transition-colors hover:text-indigo-600"
                    >Unit Keluarga</Link
                >
                <span class="text-gray-300">/</span>
                <span class="font-medium text-gray-900">{{
                    isEdit ? 'Edit' : 'Buat Baru'
                }}</span>
            </nav>

            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="border-b border-gray-100 px-6 py-5">
                    <h1 class="text-lg font-semibold text-gray-900">
                        {{
                            isEdit
                                ? `Edit: ${familyUnit.name}`
                                : 'Unit Keluarga Baru'
                        }}
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{
                            isEdit
                                ? 'Perbarui informasi unit keluarga.'
                                : 'Isi detail untuk unit keluarga baru.'
                        }}
                    </p>
                </div>

                <div class="space-y-5 px-6 py-6">
                    <!-- Nama -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Nama Unit Keluarga
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            maxlength="100"
                            placeholder="Contoh: Trah Sonosewojo"
                            :class="[
                                'w-full rounded-lg border px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300',
                                form.errors.name
                                    ? 'border-red-300 bg-red-50'
                                    : 'border-gray-300',
                            ]"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                            >Deskripsi</label
                        >
                        <textarea
                            v-model="form.description"
                            rows="3"
                            maxlength="500"
                            placeholder="Deskripsi singkat tentang unit keluarga ini (opsional)..."
                            :class="[
                                'w-full resize-none rounded-lg border px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300',
                                form.errors.description
                                    ? 'border-red-300 bg-red-50'
                                    : 'border-gray-300',
                            ]"
                        />
                        <p class="mt-1 text-xs text-gray-400">
                            {{ form.description.length }}/500
                        </p>
                    </div>

                    <!-- Moderator (multi-select, max 3) -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Moderator
                            <span class="ml-1 text-xs font-normal text-gray-400"
                                >({{ form.moderator_ids.length }}/3
                                dipilih)</span
                            >
                        </label>

                        <div
                            v-if="moderators.length === 0"
                            class="rounded-lg border border-dashed border-gray-200 py-3 text-center text-sm italic text-gray-400"
                        >
                            Belum ada akun dengan role moderator.
                        </div>

                        <div
                            v-else
                            class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3"
                        >
                            <label
                                v-for="mod in moderators"
                                :key="mod.id"
                                :class="[
                                    'flex items-center gap-3 rounded-lg p-2 transition-colors',
                                    isModeratorDisabled(mod)
                                        ? 'cursor-not-allowed opacity-40'
                                        : 'cursor-pointer',
                                    form.moderator_ids.includes(mod.id)
                                        ? 'border border-indigo-200 bg-indigo-50'
                                        : 'border border-transparent hover:bg-gray-50',
                                ]"
                            >
                                <input
                                    type="checkbox"
                                    :value="mod.id"
                                    v-model="form.moderator_ids"
                                    :disabled="isModeratorDisabled(mod)"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-medium text-gray-900"
                                    >
                                        {{ mod.name }}
                                        <span
                                            v-if="
                                                String(mod.id) ===
                                                String(currentUserId)
                                            "
                                            class="ml-1 text-xs font-normal text-gray-400"
                                            >(kamu)</span
                                        >
                                    </p>
                                    <p class="truncate text-xs text-gray-400">
                                        {{ mod.email }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <p class="mt-1 text-xs text-gray-400">
                            Maksimal 3 moderator per unit. Moderator yang
                            dipilih bisa mengelola data anggota di unit ini.
                        </p>
                        <p
                            v-if="form.errors.moderator_ids"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.moderator_ids }}
                        </p>
                    </div>
                </div>

                <!-- No-changes warning -->
                <div
                    v-if="noChangesWarning"
                    class="mx-6 mb-0 flex items-start gap-3 rounded-xl border border-yellow-300 bg-yellow-50 p-4"
                >
                    <span class="flex-shrink-0 text-lg text-yellow-500"
                        >⚠️</span
                    >
                    <div>
                        <p class="text-sm font-semibold text-yellow-800">
                            Tidak Ada Perubahan
                        </p>
                        <p class="mt-0.5 text-xs text-yellow-700">
                            Data tidak disimpan karena tidak ada perubahan yang
                            terdeteksi.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-6 py-4"
                >
                    <Link
                        href="/family"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Batal
                    </Link>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 disabled:opacity-60"
                    >
                        <svg
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            />
                        </svg>
                        {{ isEdit ? 'Simpan Perubahan' : 'Buat Unit Keluarga' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
