<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    familyUnit: { type: Object, required: true },
    members: { type: Array, default: () => [] },
    relationships: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
})

const page = usePage()
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')

// ── HELPERS ───────────────────────────────────────────────────────────────

const genderConfig = (gender) =>
    ({
        male: { label: 'Laki-laki', bg: 'bg-blue-100 text-blue-700' },
        female: { label: 'Perempuan', bg: 'bg-pink-100 text-pink-700' },
        unknown: { label: '?', bg: 'bg-gray-100 text-gray-500' },
    })[gender] ?? { label: '?', bg: 'bg-gray-100 text-gray-500' }

const formatYear = (dateStr) => {
    if (!dateStr) return null
    return parseInt(String(dateStr).split('T')[0].split('-')[0], 10)
}

const typeLabel = (type) =>
    ({
        parent: 'Orang Tua Kandung',
        step_parent: 'Orang Tua Tiri',
        adopted_parent: 'Orang Tua Angkat',
        spouse: 'Pasangan',
    })[type] ?? type

const statusBadge = (status) =>
    ({
        approved: { label: 'Aktif', cls: 'bg-green-100 text-green-700' },
        pending: { label: 'Pending', cls: 'bg-amber-100 text-amber-700' },
    })[status] ?? { label: status, cls: 'bg-gray-100 text-gray-600' }

// ── ADD RELATION MODAL ────────────────────────────────────────────────────

const showRelModal = ref(false)
const relProcessing = ref(false)
const relErrors = ref({})
const relForm = ref({
    person_id: '',
    related_id: '',
    type: 'parent',
    is_biological: true,
})

const relationTypes = [
    { value: 'parent', label: 'Orang Tua Kandung' },
    { value: 'step_parent', label: 'Orang Tua Tiri' },
    { value: 'adopted_parent', label: 'Orang Tua Angkat' },
    { value: 'child', label: 'Anak Kandung' },
    { value: 'step_child', label: 'Anak Tiri' },
    { value: 'adopted_child', label: 'Anak Angkat' },
    { value: 'spouse', label: 'Pasangan / Suami-Istri' },
]

// Anggota selain yang dipilih di person_id
const availableRelated = computed(() =>
    props.members.filter((m) => m.id !== relForm.value.person_id),
)

const openRelModal = () => {
    relForm.value = {
        person_id: '',
        related_id: '',
        type: 'parent',
        is_biological: true,
    }
    relErrors.value = {}
    showRelModal.value = true
}

const submitRelation = () => {
    relErrors.value = {}
    if (!relForm.value.person_id) {
        relErrors.value.person_id = 'Pilih anggota pertama.'
        return
    }
    if (!relForm.value.related_id) {
        relErrors.value.related_id = 'Pilih anggota kedua.'
        return
    }

    relProcessing.value = true
    router.post(
        '/relationships',
        {
            person_id: relForm.value.person_id,
            related_id: relForm.value.related_id,
            type: relForm.value.type,
            is_biological: relForm.value.is_biological,
        },
        {
            onSuccess: () => {
                showRelModal.value = false
            },
            onError: (e) => {
                relErrors.value = e
            },
            onFinish: () => {
                relProcessing.value = false
            },
        },
    )
}

// ── DELETE RELATION ───────────────────────────────────────────────────────

const showDelRelModal = ref(false)
const delRelTarget = ref(null)
const delRelProcessing = ref(false)

const confirmDelRelation = (rel) => {
    delRelTarget.value = rel
    showDelRelModal.value = true
}

const submitDelRelation = () => {
    delRelProcessing.value = true
    router.delete(`/relationships/${delRelTarget.value.id}`, {
        data: { reason: 'Dihapus dari halaman unit keluarga' },
        onSuccess: () => {
            showDelRelModal.value = false
            delRelTarget.value = null
        },
        onFinish: () => {
            delRelProcessing.value = false
        },
    })
}

const getName = (id) =>
    props.members.find((m) => m.id === id)?.display_name ?? '—'
</script>

<template>
    <Head :title="familyUnit.name" />
    <AppLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link
                    href="/family"
                    class="transition-colors hover:text-indigo-600"
                    >Unit Keluarga</Link
                >
                <span class="text-gray-300">/</span>
                <span class="font-medium text-gray-800">{{
                    familyUnit.name
                }}</span>
            </nav>

            <!-- Flash -->
            <div
                v-if="$page.props.flash?.message"
                class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            >
                ✓ {{ $page.props.flash.message }}
            </div>

            <!-- Header card -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-start justify-between bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 py-6"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-white/20 text-3xl"
                        >
                            🏠
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">
                                {{ familyUnit.name }}
                            </h1>
                            <p
                                v-if="familyUnit.description"
                                class="mt-1 text-sm text-indigo-200"
                            >
                                {{ familyUnit.description }}
                            </p>
                            <div
                                class="mt-2 flex items-center gap-4 text-sm text-indigo-100"
                            >
                                <span>{{ members.length }} anggota aktif</span>
                                <span v-if="familyUnit.moderator">
                                    · Moderator:
                                    <span class="font-semibold text-white">{{
                                        familyUnit.moderator.name
                                    }}</span>
                                </span>
                                <span v-else class="italic text-indigo-300"
                                    >· Belum ada moderator</span
                                >
                            </div>
                        </div>
                    </div>
                    <!-- Admin actions -->
                    <div
                        v-if="can.edit"
                        class="flex flex-shrink-0 items-center gap-2"
                    >
                        <Link
                            :href="`/family/${familyUnit.id}/edit`"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-white/20 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-white/30"
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
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Daftar anggota (2/3) -->
                <div class="space-y-4 lg:col-span-2">
                    <div
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
                        >
                            <h2 class="text-sm font-semibold text-gray-900">
                                Anggota Keluarga
                            </h2>
                            <span
                                class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-400"
                                >{{ members.length }}</span
                            >
                        </div>

                        <div
                            v-if="members.length === 0"
                            class="px-5 py-12 text-center text-gray-400"
                        >
                            <p class="text-sm">
                                Belum ada anggota aktif di unit ini.
                            </p>
                        </div>

                        <div v-else class="divide-y divide-gray-50">
                            <Link
                                v-for="member in members"
                                :key="member.id"
                                :href="`/people/${member.id}`"
                                class="group flex items-center gap-3 px-5 py-3.5 transition-colors hover:bg-gray-50"
                            >
                                <div
                                    :class="[
                                        'flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                        genderConfig(member.gender).bg,
                                    ]"
                                >
                                    {{
                                        member.display_name
                                            .charAt(0)
                                            .toUpperCase()
                                    }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-medium text-gray-900 transition-colors group-hover:text-indigo-600"
                                    >
                                        {{ member.display_name }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        {{ genderConfig(member.gender).label }}
                                        <span
                                            v-if="formatYear(member.birth_date)"
                                        >
                                            ·
                                            {{
                                                formatYear(member.birth_date)
                                            }}</span
                                        >
                                    </p>
                                </div>
                                <svg
                                    class="h-4 w-4 flex-shrink-0 text-gray-300 transition-colors group-hover:text-indigo-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Relasi (1/3) -->
                <div class="space-y-4">
                    <div
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
                        >
                            <h2 class="text-sm font-semibold text-gray-900">
                                Relasi Internal
                            </h2>
                            <button
                                v-if="
                                    can.manage_relations && members.length >= 2
                                "
                                @click="openRelModal"
                                class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 transition-colors hover:text-indigo-800"
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Tambah
                            </button>
                        </div>

                        <div
                            v-if="relationships.length === 0"
                            class="px-5 py-8 text-center text-sm text-gray-400"
                        >
                            <p>Belum ada relasi antar anggota.</p>
                            <p v-if="members.length < 2" class="mt-1 text-xs">
                                Perlu minimal 2 anggota.
                            </p>
                        </div>

                        <div v-else class="divide-y divide-gray-50">
                            <div
                                v-for="rel in relationships"
                                :key="rel.id"
                                class="group flex items-start gap-3 px-5 py-3"
                            >
                                <div class="min-w-0 flex-1 text-xs">
                                    <p
                                        class="truncate font-medium text-gray-900"
                                    >
                                        {{ getName(rel.subject_id) }}
                                    </p>
                                    <p class="my-0.5 text-gray-400">
                                        {{ typeLabel(rel.type) }}
                                    </p>
                                    <p
                                        class="truncate font-medium text-gray-900"
                                    >
                                        {{ getName(rel.object_id) }}
                                    </p>
                                    <span
                                        :class="[
                                            'mt-1 inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold',
                                            statusBadge(rel.status).cls,
                                        ]"
                                    >
                                        {{ statusBadge(rel.status).label }}
                                    </span>
                                </div>
                                <button
                                    v-if="can.manage_relations"
                                    @click="confirmDelRelation(rel)"
                                    class="mt-1 flex-shrink-0 p-1 text-gray-300 opacity-0 transition-all hover:text-red-500 group-hover:opacity-100"
                                    title="Hapus relasi"
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
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add relation modal -->
        <Teleport to="body">
            <div
                v-if="showRelModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <h3 class="mb-1 text-base font-semibold text-gray-900">
                        Tambah Relasi
                    </h3>
                    <p class="mb-5 text-xs text-gray-500">
                        Hanya anggota di unit keluarga
                        <span class="font-medium">{{ familyUnit.name }}</span>
                        yang tersedia.
                    </p>

                    <div class="space-y-4">
                        <!-- Anggota pertama -->
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-700"
                                >Anggota pertama
                                <span class="text-red-500">*</span></label
                            >
                            <select
                                v-model="relForm.person_id"
                                :class="[
                                    'w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300',
                                    relErrors.person_id
                                        ? 'border-red-300'
                                        : 'border-gray-300',
                                ]"
                            >
                                <option value="">Pilih anggota...</option>
                                <option
                                    v-for="m in members"
                                    :key="m.id"
                                    :value="m.id"
                                >
                                    {{ m.display_name }}
                                </option>
                            </select>
                            <p
                                v-if="relErrors.person_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ relErrors.person_id }}
                            </p>
                        </div>

                        <!-- Jenis relasi -->
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-700"
                                >Jenis relasi</label
                            >
                            <select
                                v-model="relForm.type"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                            >
                                <option
                                    v-for="t in relationTypes"
                                    :key="t.value"
                                    :value="t.value"
                                >
                                    {{ t.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Anggota kedua -->
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-700"
                                >Anggota kedua
                                <span class="text-red-500">*</span></label
                            >
                            <select
                                v-model="relForm.related_id"
                                :disabled="!relForm.person_id"
                                :class="[
                                    'w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 disabled:bg-gray-50 disabled:text-gray-400',
                                    relErrors.related_id
                                        ? 'border-red-300'
                                        : 'border-gray-300',
                                ]"
                            >
                                <option value="">
                                    {{
                                        relForm.person_id
                                            ? 'Pilih anggota...'
                                            : 'Pilih anggota pertama dulu'
                                    }}
                                </option>
                                <option
                                    v-for="m in availableRelated"
                                    :key="m.id"
                                    :value="m.id"
                                >
                                    {{ m.display_name }}
                                </option>
                            </select>
                            <p
                                v-if="relErrors.related_id"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ relErrors.related_id }}
                            </p>
                        </div>

                        <!-- Biologis (selain pasangan) -->
                        <label
                            v-if="relForm.type !== 'spouse'"
                            class="flex cursor-pointer items-center gap-2 text-sm text-gray-600"
                        >
                            <input
                                type="checkbox"
                                v-model="relForm.is_biological"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            Hubungan biologis / kandung
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="showRelModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitRelation"
                            :disabled="relProcessing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{
                                relProcessing ? 'Menyimpan...' : 'Simpan Relasi'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete relation confirm -->
        <Teleport to="body">
            <div
                v-if="showDelRelModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                    <h3 class="text-base font-semibold text-gray-900">
                        Hapus Relasi?
                    </h3>
                    <p class="mb-5 mt-1 text-sm text-gray-500">
                        Relasi antara
                        <span class="font-medium">{{
                            getName(delRelTarget?.subject_id)
                        }}</span>
                        dan
                        <span class="font-medium">{{
                            getName(delRelTarget?.object_id)
                        }}</span>
                        akan dihapus.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="showDelRelModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitDelRelation"
                            :disabled="delRelProcessing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            {{
                                delRelProcessing ? 'Menghapus...' : 'Ya, Hapus'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
