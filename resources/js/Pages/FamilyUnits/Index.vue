<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    familyUnits: Array,
    can: Object,
})

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleteProcessing = ref(false)

const confirmDelete = (unit) => {
    deleteTarget.value = unit
    showDeleteModal.value = true
}

const submitDelete = () => {
    deleteProcessing.value = true
    router.delete(`/family/${deleteTarget.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false
            deleteTarget.value = null
        },
        onFinish: () => {
            deleteProcessing.value = false
        },
    })
}
</script>

<template>
    <Head title="Unit Keluarga" />
    <AppLayout>
        <div class="space-y-5">
            <!-- Header -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-semibold text-gray-950">
                        Unit Keluarga
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ familyUnits.length }} unit terdaftar
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    href="/family/create"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Tambah Unit
                </Link>
            </div>

            <!-- Flash -->
            <div
                v-if="$page.props.flash?.message"
                class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            >
                ✓ {{ $page.props.flash.message }}
            </div>
            <div
                v-if="$page.props.flash?.error"
                class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                ⚠ {{ $page.props.flash.error }}
            </div>

            <!-- Empty -->
            <div
                v-if="familyUnits.length === 0"
                class="rounded-xl border border-gray-200 bg-white px-6 py-20 text-center shadow-sm"
            >
                <div class="mb-4 text-5xl">🏡</div>
                <p class="text-base font-medium text-gray-600">
                    Belum ada unit keluarga
                </p>
                <p class="mb-4 mt-1 text-sm text-gray-400">
                    Buat unit keluarga pertama untuk mulai mendaftarkan anggota.
                </p>
                <Link
                    v-if="can.create"
                    href="/family/create"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-500"
                >
                    Buat Unit Keluarga
                </Link>
            </div>

            <!-- Grid -->
            <div
                v-else
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="unit in familyUnits"
                    :key="unit.id"
                    class="group flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-indigo-200 hover:shadow-md"
                >
                    <!-- Icon + name -->
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-2xl"
                        >
                            🏠
                        </div>
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="`/family/${unit.id}`"
                                class="block truncate font-semibold text-gray-900 transition-colors hover:text-indigo-600"
                            >
                                {{ unit.name }}
                            </Link>
                            <p
                                v-if="unit.description"
                                class="mt-0.5 line-clamp-2 text-xs text-gray-500"
                            >
                                {{ unit.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            {{ unit.people_count ?? 0 }} anggota
                        </span>
                        <span
                            v-if="unit.moderator?.name"
                            class="flex items-center gap-1"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            {{ unit.moderator.name }}
                        </span>
                        <span v-else class="italic text-gray-400"
                            >Belum ada moderator</span
                        >
                    </div>

                    <!-- Actions -->
                    <div
                        v-if="can.edit || can.delete"
                        class="flex items-center gap-2 border-t border-gray-100 pt-2"
                    >
                        <Link
                            v-if="can.edit"
                            :href="`/family/${unit.id}/edit`"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-100"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"
                                />
                            </svg>
                            Edit
                        </Link>
                        <button
                            v-if="can.delete"
                            @click="confirmDelete(unit)"
                            class="rounded-lg border border-transparent p-1.5 text-gray-400 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete modal -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100"
                        >
                            <svg
                                class="h-5 w-5 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Hapus Unit Keluarga?
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Unit
                                <span class="font-medium">{{
                                    deleteTarget?.name
                                }}</span>
                                akan dihapus permanen. Unit yang masih memiliki
                                anggota tidak bisa dihapus.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            @click="showDeleteModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitDelete"
                            :disabled="deleteProcessing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            {{
                                deleteProcessing ? 'Menghapus...' : 'Ya, Hapus'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
