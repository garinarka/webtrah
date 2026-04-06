<script setup>
// Dipakai untuk Create DAN Edit — bedanya hanya props yang diterima
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    familyUnit: { type: Object, default: null }, // null = create mode
    moderators: { type: Array, default: () => [] },
})

const isEdit = !!props.familyUnit

const form = useForm({
    name: props.familyUnit?.name ?? '',
    description: props.familyUnit?.description ?? '',
    moderator_id: props.familyUnit?.moderator_id ?? '',
})

const submit = () => {
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

                    <!-- Moderator -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                            >Moderator</label
                        >
                        <select
                            v-model="form.moderator_id"
                            :class="[
                                'w-full rounded-lg border bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300',
                                form.errors.moderator_id
                                    ? 'border-red-300'
                                    : 'border-gray-300',
                            ]"
                        >
                            <option value="">
                                Tidak ada moderator (opsional)
                            </option>
                            <option
                                v-for="mod in moderators"
                                :key="mod.id"
                                :value="mod.id"
                            >
                                {{ mod.name }} — {{ mod.email }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-400">
                            Moderator bisa mengelola data anggota di unit ini
                            (perlu persetujuan admin).
                        </p>
                        <p
                            v-if="form.errors.moderator_id"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.moderator_id }}
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
