<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import RelationshipsPanel from '@/Pages/People/Partials/RelationshipsPanel.vue'

const props = defineProps({
    person: Object,
    auditLogs: { type: Array, default: () => [] },
    relationships: {
        type: Object,
        default: () => ({
            parents: [],
            children: [],
            spouses: [],
            pending: [],
        }),
    },
    peopleList: { type: Array, default: () => [] },
    pendingApprovalId: { type: Number, default: null },
    can: Object,
})

// ── DATE FORMATTING ──────────────────────────────────────────────────────────
const formatDate = (rawDate, accuracy) => {
    if (!rawDate) return null
    // Strip timezone noise — ambil hanya bagian tanggal saja
    const dateOnly = String(rawDate).split('T')[0]
    if (!dateOnly || accuracy === 'unknown') return null

    try {
        // Gunakan date string murni agar tidak kena timezone shift
        const [year, month, day] = dateOnly.split('-').map(Number)
        const d = new Date(year, month - 1, day)

        if (accuracy === 'year') return String(year)
        if (accuracy === 'year_month')
            return d.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
            })
        return d.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        })
    } catch {
        return dateOnly
    }
}

const accuracyLabel = (accuracy) =>
    ({
        exact: null,
        year_month: 'perkiraan bulan',
        year: 'perkiraan tahun',
        unknown: null,
    })[accuracy] ?? null

const isDeceased = props.person.death_date != null

// ── STATUS CONFIG ────────────────────────────────────────────────────────────
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
        disputed: {
            label: 'Disputed',
            cls: 'bg-red-50 text-red-700 ring-red-600/20',
        },
        archived: {
            label: 'Arsip',
            cls: 'bg-gray-100 text-gray-500 ring-gray-400/20',
        },
    })[status] ?? {
        label: status,
        cls: 'bg-gray-50 text-gray-600 ring-gray-500/20',
    }

const genderLabel = (gender) =>
    ({
        male: 'Laki-laki',
        female: 'Perempuan',
        unknown: 'Tidak Diketahui',
    })[gender] ?? '-'

// ── DELETE MODAL ─────────────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const deleteReason = ref('')
const deleteProcessing = ref(false)
const cascadeAck = ref(false)

// Relasi pasangan aktif yang akan ikut diakhiri otomatis (Opsi A: cascade)
const activeSpouses = computed(() => props.relationships?.spouses ?? [])
const hasActiveSpouses = computed(() => activeSpouses.value.length > 0)

const canSubmitDelete = computed(
    () =>
        deleteReason.value.length >= 5 &&
        (!hasActiveSpouses.value || cascadeAck.value),
)

const submitDelete = () => {
    if (!canSubmitDelete.value) return
    deleteProcessing.value = true
    router.delete(`/people/${props.person.id}`, {
        data: { reason: deleteReason.value },
        onFinish: () => {
            deleteProcessing.value = false
        },
    })
}

// ── CANCEL APPROVAL ───────────────────────────────────────────────────────────
// Batalkan pengajuan yang masih pending (create/update/delete)
// Hanya muncul jika user ini adalah pengaju dan approval masih pending
const cancelProcessing = ref(false)

const cancelApproval = () => {
    if (!props.pendingApprovalId || cancelProcessing.value) return
    cancelProcessing.value = true
    router.delete(`/approvals/${props.pendingApprovalId}/cancel`, {
        onFinish: () => {
            cancelProcessing.value = false
        },
    })
}

// ── AUDIT LOG ────────────────────────────────────────────────────────────────
const eventLabel = (event) =>
    ({
        created: 'Dibuat',
        updated: 'Diperbarui',
        draft_saved: 'Draft Tersimpan',
        delete_requested: 'Hapus Diminta',
        update_requested: 'Ubah Diminta',
        deleted: 'Dihapus',
    })[event] ?? event

const eventColor = (event) =>
    ({
        created: 'text-green-700 bg-green-50 ring-green-600/20',
        updated: 'text-blue-700 bg-blue-50 ring-blue-600/20',
        draft_saved: 'text-gray-600 bg-gray-50 ring-gray-500/20',
        delete_requested: 'text-red-700 bg-red-50 ring-red-600/20',
        update_requested: 'text-amber-700 bg-amber-50 ring-amber-600/20',
        deleted: 'text-red-800 bg-red-100 ring-red-700/20',
    })[event] ?? 'text-gray-600 bg-gray-50 ring-gray-500/20'
</script>

<template>
    <Head :title="person.display_name" />
    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Flash message -->
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

            <!-- Breadcrumb + actions header -->
            <div class="flex items-center justify-between">
                <nav class="flex items-center gap-2 text-sm text-gray-500">
                    <Link
                        href="/people"
                        class="transition-colors hover:text-indigo-600"
                        >Anggota Keluarga</Link
                    >
                    <span class="text-gray-300">/</span>
                    <span class="font-medium text-gray-800">{{
                        person.display_name
                    }}</span>
                </nav>
                <div class="flex items-center gap-2">
                    <!-- Batalkan pengajuan (jika masih pending) -->
                    <button
                        v-if="can.cancel_approval"
                        @click="cancelApproval"
                        :disabled="cancelProcessing"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-100 disabled:opacity-50"
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
                        {{
                            cancelProcessing
                                ? 'Membatalkan...'
                                : 'Batalkan Pengajuan'
                        }}
                    </button>
                    <Link
                        v-if="can.edit"
                        :href="`/people/${person.id}/edit`"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
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
                        @click="showDeleteModal = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 transition-colors hover:bg-red-100"
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
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"
                            />
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>

            <!-- Main profile card -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <!-- Header gradient -->
                <div
                    class="flex items-center gap-5 bg-gradient-to-br from-indigo-600 to-indigo-500 px-6 py-6"
                >
                    <div
                        :class="[
                            'flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl text-2xl font-bold text-white',
                            person.gender === 'male'
                                ? 'bg-blue-400/60'
                                : person.gender === 'female'
                                  ? 'bg-pink-400/60'
                                  : 'bg-indigo-300/60',
                        ]"
                    >
                        {{ person.display_name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            {{ person.display_name }}
                        </h1>
                        <div class="mt-1.5 flex items-center gap-2">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                    statusConfig(person.status).cls,
                                ]"
                            >
                                {{ statusConfig(person.status).label }}
                            </span>
                            <span
                                v-if="isDeceased"
                                class="text-xs text-indigo-200"
                                >† Almarhum/Almarhumah</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Detail fields -->
                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                        <!-- Gender -->
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Jenis Kelamin
                            </dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ genderLabel(person.gender) }}
                            </dd>
                        </div>

                        <!-- Tanggal Lahir -->
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Tanggal Lahir
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <template
                                    v-if="
                                        formatDate(
                                            person.birth_date,
                                            person.birth_accuracy,
                                        )
                                    "
                                >
                                    <span class="font-medium">{{
                                        formatDate(
                                            person.birth_date,
                                            person.birth_accuracy,
                                        )
                                    }}</span>
                                    <span
                                        v-if="
                                            accuracyLabel(person.birth_accuracy)
                                        "
                                        class="ml-1 text-xs text-gray-400"
                                    >
                                        ({{
                                            accuracyLabel(
                                                person.birth_accuracy,
                                            )
                                        }})
                                    </span>
                                </template>
                                <span v-else class="italic text-gray-400"
                                    >Tidak diketahui</span
                                >
                            </dd>
                        </div>

                        <!-- Tanggal Meninggal — hanya tampil jika meninggal -->
                        <div v-if="isDeceased">
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Tanggal Meninggal
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <template
                                    v-if="
                                        formatDate(
                                            person.death_date,
                                            person.death_accuracy,
                                        )
                                    "
                                >
                                    <span class="font-medium">{{
                                        formatDate(
                                            person.death_date,
                                            person.death_accuracy,
                                        )
                                    }}</span>
                                    <span
                                        v-if="
                                            accuracyLabel(person.death_accuracy)
                                        "
                                        class="ml-1 text-xs text-gray-400"
                                    >
                                        ({{
                                            accuracyLabel(
                                                person.death_accuracy,
                                            )
                                        }})
                                    </span>
                                </template>
                                <span v-else class="italic text-gray-400"
                                    >Tidak diketahui</span
                                >
                            </dd>
                        </div>

                        <!-- Unit Keluarga -->
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Unit Keluarga
                            </dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ person.family_unit?.name ?? '—' }}
                            </dd>
                        </div>

                        <!-- Dibuat oleh -->
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Dibuat Oleh
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ person.creator?.name ?? 'System' }}
                            </dd>
                        </div>

                        <!-- Tanggal dibuat -->
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Tanggal Dibuat
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{
                                    new Date(
                                        person.created_at,
                                    ).toLocaleDateString('id-ID', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })
                                }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Relasi Keluarga -->
            <RelationshipsPanel
                v-if="
                    can.manage_relations ||
                    relationships.parents.length ||
                    relationships.children.length ||
                    relationships.spouses.length
                "
                :person-id="person.id"
                :relationships="relationships"
                :people-list="peopleList"
                :can="can"
            />

            <!-- Audit Log -->
            <div
                v-if="auditLogs.length > 0"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h3
                    class="mb-4 flex items-center gap-2 text-sm font-semibold text-gray-900"
                >
                    <svg
                        class="h-4 w-4 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        />
                    </svg>
                    Riwayat Perubahan
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="log in auditLogs"
                        :key="log.id"
                        class="flex items-start gap-3 border-b border-gray-50 pb-3 last:border-0 last:pb-0"
                    >
                        <span
                            :class="[
                                'mt-0.5 flex-shrink-0 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                eventColor(log.event),
                            ]"
                        >
                            {{ eventLabel(log.event) }}
                        </span>
                        <div>
                            <p class="text-xs text-gray-600">
                                oleh
                                <span class="font-medium text-gray-800">{{
                                    log.user?.name ?? 'System'
                                }}</span>
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{
                                    new Date(log.created_at).toLocaleString(
                                        'id-ID',
                                        {
                                            day: '2-digit',
                                            month: 'short',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        },
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── DELETE MODAL ── -->
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
                                    person.display_name
                                }}</span
                                >? Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <div class="mt-4">
                                <label
                                    class="mb-1 block text-xs font-medium text-gray-700"
                                >
                                    Alasan penghapusan
                                    <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="deleteReason"
                                    rows="2"
                                    placeholder="Minimal 5 karakter..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-300"
                                />
                            </div>

                            <!-- Peringatan cascade: relasi pasangan aktif akan ikut diakhiri -->
                            <div
                                v-if="hasActiveSpouses"
                                class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3"
                            >
                                <p class="text-xs font-semibold text-amber-800">
                                    ⚠ {{ person.display_name }} masih punya
                                    {{ activeSpouses.length }} relasi pasangan
                                    aktif. Relasi berikut akan
                                    <span class="underline"
                                        >otomatis diakhiri</span
                                    >
                                    bersamaan dengan penghapusan ini:
                                </p>
                                <ul
                                    class="mt-2 space-y-1 text-xs text-amber-700"
                                >
                                    <li
                                        v-for="s in activeSpouses"
                                        :key="s.id"
                                        class="flex items-center gap-1"
                                    >
                                        • {{ s.person?.display_name ?? '-' }}
                                    </li>
                                </ul>
                                <label
                                    class="mt-3 flex cursor-pointer items-start gap-2 text-xs text-amber-900"
                                >
                                    <input
                                        v-model="cascadeAck"
                                        type="checkbox"
                                        class="mt-0.5 h-3.5 w-3.5 rounded border-amber-400 text-amber-600 focus:ring-amber-400"
                                    />
                                    Saya paham dan setuju relasi pasangan di
                                    atas akan diakhiri secara otomatis.
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            @click="showDeleteModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitDelete"
                            :disabled="!canSubmitDelete || deleteProcessing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-50"
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
