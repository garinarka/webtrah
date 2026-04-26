<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
    people: Object,
    filters: Object,
    can: Object,
    draftCount: { type: Number, default: 0 },
})

const page = usePage()
const auth = useAuthStore()
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')
const isModerator = computed(() =>
    ['admin', 'moderator'].includes(page.props.auth?.user?.role),
)

// Moderator hanya bisa edit/delete person di unit keluarganya
// Admin bisa semua
const canEditPerson = (person) => {
    if (!props.can.edit) return false
    if (isAdmin.value) return true
    return auth.canManageUnit(person.family_unit_id)
}
const canDeletePerson = (person) => {
    if (!props.can.delete) return false
    if (isAdmin.value) return true
    return auth.canManageUnit(person.family_unit_id)
}

// ── FILTERS ──────────────────────────────────────────────────────────────────

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const genderFilter = ref(props.filters.gender || '')

const applyFilters = () => {
    const params = {}
    if (search.value) params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value
    if (genderFilter.value) params.gender = genderFilter.value
    router.get('/people', params, { preserveState: true, replace: true })
}

const clearFilters = () => {
    search.value = ''
    statusFilter.value = ''
    genderFilter.value = ''
    router.get('/people', {}, { preserveState: true, replace: true })
}

let searchTimer
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 350)
})
watch([statusFilter, genderFilter], applyFilters)

const hasActiveFilters = computed(
    () => search.value || statusFilter.value || genderFilter.value,
)

// ── SELECTION ─────────────────────────────────────────────────────────────────

const selected = ref([])
const allChecked = computed(
    () =>
        props.people.data.length > 0 &&
        selected.value.length === props.people.data.length,
)
const someChecked = computed(
    () =>
        selected.value.length > 0 &&
        selected.value.length < props.people.data.length,
)

const toggleAll = () => {
    selected.value = allChecked.value ? [] : props.people.data.map((p) => p.id)
}
const toggleOne = (id) => {
    selected.value = selected.value.includes(id)
        ? selected.value.filter((x) => x !== id)
        : [...selected.value, id]
}

// ── BULK DELETE ───────────────────────────────────────────────────────────────

const showBulkDeleteModal = ref(false)
const bulkReason = ref('')
const bulkProcessing = ref(false)

const confirmBulkDelete = () => {
    if (selected.value.length) {
        showBulkDeleteModal.value = true
        bulkReason.value = ''
    }
}
const submitBulkDelete = () => {
    if (!bulkReason.value || bulkReason.value.length < 5) return
    bulkProcessing.value = true
    router.delete('/people/bulk-destroy', {
        data: { ids: selected.value, reason: bulkReason.value },
        onSuccess: () => {
            selected.value = []
            showBulkDeleteModal.value = false
            bulkReason.value = ''
        },
        onFinish: () => {
            bulkProcessing.value = false
        },
    })
}

// ── SINGLE DELETE ─────────────────────────────────────────────────────────────

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleteReason = ref('')
const deleteProcessing = ref(false)

const confirmDelete = (person) => {
    deleteTarget.value = person
    deleteReason.value = ''
    showDeleteModal.value = true
}
const submitDelete = () => {
    if (!deleteReason.value || deleteReason.value.length < 5) return
    deleteProcessing.value = true
    router.delete(`/people/${deleteTarget.value.id}`, {
        data: { reason: deleteReason.value },
        onSuccess: () => {
            showDeleteModal.value = false
            deleteTarget.value = null
        },
        onFinish: () => {
            deleteProcessing.value = false
        },
    })
}

// ── HELPERS ───────────────────────────────────────────────────────────────────

const statusConfig = (status) =>
    ({
        active: {
            label: 'Aktif',
            cls: 'bg-green-50 text-green-700 ring-green-600/20',
        },
        pending: {
            label: 'Pending',
            cls: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        },
        draft: {
            label: 'Draft',
            cls: 'bg-gray-50 text-gray-600 ring-gray-500/20',
        },
        archived: {
            label: 'Arsip',
            cls: 'bg-gray-100 text-gray-500 ring-gray-400/20',
        },
    })[status] ?? {
        label: status,
        cls: 'bg-gray-50 text-gray-600 ring-gray-500/20',
    }

const genderConfig = (gender) =>
    ({
        male: { label: 'L', bg: 'bg-blue-100 text-blue-700' },
        female: { label: 'P', bg: 'bg-pink-100 text-pink-700' },
        unknown: { label: '?', bg: 'bg-gray-100 text-gray-500' },
    })[gender] ?? { label: '?', bg: 'bg-gray-100 text-gray-500' }

/**
 * Ambil tahun dari string tanggal tanpa parse sebagai UTC.
 * new Date("2025-01-01").getFullYear() → 2024 di UTC+7 (midnight UTC = malam sebelumnya).
 * Fix: ambil komponen langsung dari string.
 */
const formatYear = (dateStr) => {
    if (!dateStr) return null
    return parseInt(String(dateStr).split('T')[0].split('-')[0], 10)
}
</script>

<template>
    <Head title="Daftar Anggota" />
    <AppLayout>
        <div class="space-y-5">
            <!-- Header -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-semibold text-gray-950">
                        Anggota Keluarga
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ people.total }} total anggota terdaftar
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Draft button — inactive (disabled) kalau tidak ada draft -->
                    <Link
                        href="/people/drafts"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-medium transition-colors',
                            draftCount > 0
                                ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                : 'pointer-events-none cursor-not-allowed border-gray-200 bg-gray-50 text-gray-300',
                        ]"
                        :aria-disabled="draftCount === 0"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.75"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                            />
                        </svg>
                        Draft
                        <span
                            v-if="draftCount > 0"
                            :class="[
                                'inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-xs font-semibold',
                                'bg-amber-100 text-amber-700',
                            ]"
                            >{{ draftCount }}</span
                        >
                    </Link>

                    <Link
                        v-if="can.create"
                        href="/people/create"
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
                        Tambah Anggota
                    </Link>
                </div>
            </div>

            <!-- Flash -->
            <div
                v-if="$page.props.flash?.message"
                class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            >
                <svg
                    class="h-4 w-4 flex-shrink-0"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd"
                    />
                </svg>
                {{ $page.props.flash.message }}
            </div>

            <!-- Table Card -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <!-- Toolbar -->
                <div
                    class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3"
                >
                    <!-- Search -->
                    <div class="relative min-w-[200px] max-w-xs flex-1">
                        <svg
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-5.197-5.197A7.5 7.5 0 105.196 5.196 7.5 7.5 0 0016.803 15.803z"
                            />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama..."
                            class="w-full rounded-lg border border-gray-300 py-1.5 pl-9 pr-3 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                        />
                    </div>

                    <!-- Status filter -->
                    <select
                        v-model="statusFilter"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="archived">Arsip</option>
                    </select>

                    <!-- Gender filter -->
                    <select
                        v-model="genderFilter"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                    >
                        <option value="">Semua Gender</option>
                        <option value="male">Laki-laki</option>
                        <option value="female">Perempuan</option>
                        <option value="unknown">Tidak Diketahui</option>
                    </select>

                    <!-- Reset filter -->
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
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
                        Reset
                    </button>

                    <!-- Bulk actions -->
                    <div
                        v-if="selected.length > 0 && can.bulk_delete"
                        class="ml-auto flex items-center gap-2"
                    >
                        <span class="text-sm font-medium text-gray-600"
                            >{{ selected.length }} dipilih</span
                        >
                        <button
                            @click="confirmBulkDelete"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 transition-colors hover:bg-red-100"
                        >
                            Hapus Terpilih
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th
                                    v-if="can.bulk_delete"
                                    class="w-10 px-4 py-3"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="allChecked"
                                        :indeterminate="someChecked"
                                        @change="toggleAll"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Nama
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 sm:table-cell"
                                >
                                    Lahir
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 md:table-cell"
                                >
                                    Unit Keluarga
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="people.data.length === 0">
                                <td
                                    :colspan="can.bulk_delete ? 6 : 5"
                                    class="px-4 py-16 text-center"
                                >
                                    <div
                                        class="flex flex-col items-center gap-2 text-gray-400"
                                    >
                                        <svg
                                            class="h-10 w-10"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                        </svg>
                                        <p class="font-medium">
                                            Tidak ada data ditemukan
                                        </p>
                                        <p class="text-xs">
                                            Coba ubah filter atau tambah anggota
                                            baru
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="person in people.data"
                                :key="person.id"
                                :class="[
                                    'group transition-colors',
                                    selected.includes(person.id)
                                        ? 'bg-indigo-50/60'
                                        : 'hover:bg-gray-50/80',
                                ]"
                            >
                                <td v-if="can.bulk_delete" class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        :checked="selected.includes(person.id)"
                                        @change="toggleOne(person.id)"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            :class="[
                                                'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                                genderConfig(person.gender).bg,
                                            ]"
                                        >
                                            {{
                                                person.display_name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                        <div>
                                            <Link
                                                :href="`/people/${person.id}`"
                                                class="font-medium text-gray-900 transition-colors hover:text-indigo-600"
                                            >
                                                {{ person.display_name }}
                                            </Link>
                                            <p
                                                class="mt-0.5 text-xs text-gray-400"
                                            >
                                                {{
                                                    person.gender === 'male'
                                                        ? 'Laki-laki'
                                                        : person.gender ===
                                                            'female'
                                                          ? 'Perempuan'
                                                          : 'Tidak diketahui'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Birth year — parse dari string agar tidak kena UTC timezone bug -->
                                <td
                                    class="hidden px-4 py-3 text-gray-600 sm:table-cell"
                                >
                                    {{ formatYear(person.birth_date) ?? '—' }}
                                    <span
                                        v-if="person.death_date"
                                        class="text-gray-400"
                                    >
                                        –
                                        {{
                                            formatYear(person.death_date)
                                        }}</span
                                    >
                                </td>

                                <td
                                    class="hidden px-4 py-3 text-xs text-gray-600 md:table-cell"
                                >
                                    {{ person.family_unit?.name ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                            statusConfig(person.status).cls,
                                        ]"
                                    >
                                        {{ statusConfig(person.status).label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div
                                        class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <Link
                                            :href="`/people/${person.id}`"
                                            class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                                            title="Lihat"
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
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                            </svg>
                                        </Link>

                                        <Link
                                            v-if="canEditPerson(person)"
                                            :href="`/people/${person.id}/edit`"
                                            class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-amber-50 hover:text-amber-600"
                                            title="Edit"
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
                                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"
                                                />
                                            </svg>
                                        </Link>

                                        <button
                                            v-if="canDeletePerson(person)"
                                            @click="confirmDelete(person)"
                                            class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                            title="Hapus"
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
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="flex items-center justify-between border-t border-gray-200 bg-gray-50/50 px-4 py-3"
                >
                    <p class="text-xs text-gray-500">
                        Menampilkan {{ people.from ?? 0 }}–{{
                            people.to ?? 0
                        }}
                        dari {{ people.total }} data
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in people.links"
                            :key="i"
                            :href="link.url ?? '#'"
                            :class="[
                                'rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : link.url
                                      ? 'text-gray-600 hover:bg-gray-100'
                                      : 'cursor-not-allowed text-gray-300',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Single delete modal -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
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
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                                />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900">
                                Hapus Anggota
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Hapus
                                <span class="font-medium text-gray-700">{{
                                    deleteTarget?.display_name
                                }}</span
                                >? Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <div class="mt-4">
                                <label
                                    class="mb-1 block text-xs font-medium text-gray-700"
                                    >Alasan penghapusan
                                    <span class="text-red-500">*</span></label
                                >
                                <textarea
                                    v-model="deleteReason"
                                    rows="2"
                                    placeholder="Minimal 5 karakter..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                                />
                            </div>
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
                            :disabled="
                                deleteReason.length < 5 || deleteProcessing
                            "
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

        <!-- Bulk delete modal -->
        <Teleport to="body">
            <div
                v-if="showBulkDeleteModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
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
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"
                                />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900">
                                Hapus {{ selected.length }} Anggota
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Semua data yang dipilih akan dihapus permanen.
                            </p>
                            <div class="mt-4">
                                <label
                                    class="mb-1 block text-xs font-medium text-gray-700"
                                    >Alasan penghapusan
                                    <span class="text-red-500">*</span></label
                                >
                                <textarea
                                    v-model="bulkReason"
                                    rows="2"
                                    placeholder="Minimal 5 karakter..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            @click="showBulkDeleteModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitBulkDelete"
                            :disabled="bulkReason.length < 5 || bulkProcessing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            {{
                                bulkProcessing
                                    ? 'Memproses...'
                                    : `Hapus ${selected.length} Anggota`
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
