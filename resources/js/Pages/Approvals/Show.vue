<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    approval: Object,
    diff: Array,
    can: Object,
})

// APPROVE
const showApproveModal = ref(false)
const confirmText = ref('')

const approveForm = useForm({ confirmation: '' })
const submitApprove = () => {
    approveForm.confirmation = confirmText.value
    approveForm.post(`/approvals/${props.approval.id}/approve`, {
        onSuccess: () => {
            showApproveModal.value = false
        },
    })
}

// REJECT
const showRejectModal = ref(false)
const rejectReason = ref('')

const rejectForm = useForm({ reason: '' })
const submitReject = () => {
    rejectForm.reason = rejectReason.value
    rejectForm.post(`/approvals/${props.approval.id}/reject`, {
        onSuccess: () => {
            showRejectModal.value = false
        },
    })
}

// CANCEL
const cancelProcessing = ref(false)
const cancelApproval = () => {
    if (cancelProcessing.value) return
    cancelProcessing.value = true
    router.delete(`/approvals/${props.approval.id}/cancel`, {
        onFinish: () => {
            cancelProcessing.value = false
        },
    })
}

// HELPERS
const actionLabel = (a) =>
    ({
        create: 'Penambahan',
        update: 'Perubahan',
        delete: 'Penghapusan',
        delete_relation: 'Hapus Relasi',
        resign_moderator: 'Pengunduran Diri Moderator',
    })[a] ?? a
const actionColor = (a) =>
    ({
        create: 'bg-green-100 text-green-700 ring-green-600/20',
        update: 'bg-blue-100 text-blue-700 ring-blue-600/20',
        delete: 'bg-red-100 text-red-700 ring-red-600/20',
        delete_relation: 'bg-orange-100 text-orange-700 ring-orange-600/20',
        resign_moderator: 'bg-purple-100 text-purple-700 ring-purple-600/20',
    })[a] ?? 'bg-gray-100 text-gray-600'

const statusConfig = (s) =>
    ({
        pending: {
            label: 'Menunggu',
            cls: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        },
        approved: {
            label: 'Disetujui',
            cls: 'bg-green-50 text-green-700 ring-green-600/20',
        },
        rejected: {
            label: 'Ditolak',
            cls: 'bg-red-50 text-red-700 ring-red-600/20',
        },
    })[s] ?? { label: s, cls: 'bg-gray-50 text-gray-600' }

const formatDate = (iso) =>
    new Date(iso).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })

const typeLabel = (type) =>
    ({
        'App\\Models\\Person': 'Anggota',
        'App\\Models\\Relationship': 'Relasi',
    })[type] ?? (type ? 'Data' : 'Moderator')
</script>

<template>
    <Head title="Detail Persetujuan" />
    <AppLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <!-- breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link
                    href="/approvals"
                    class="transition-colors hover:text-indigo-600"
                    >Queue Persetujuan</Link
                >
                <span class="text-gray-300">/</span>
                <span class="font-medium text-gray-800">Detail</span>
            </nav>

            <!-- flash -->
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

            <!-- main card -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <!-- header -->
                <div class="border-b border-gray-100 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                :class="[
                                    'rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                    actionColor(approval.action),
                                ]"
                            >
                                {{ actionLabel(approval.action) }}
                            </span>
                            <span class="text-sm text-gray-500">{{
                                typeLabel(approval.approvable_type)
                            }}</span>
                        </div>
                        <span
                            :class="[
                                'flex-shrink-0 rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                statusConfig(approval.status).cls,
                            ]"
                        >
                            {{ statusConfig(approval.status).label }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{
                                approval.approvable?.display_name ?? 'Data Baru'
                            }}
                        </h2>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Diajukan oleh
                            <span class="font-medium text-gray-700">{{
                                approval.requester?.name ?? '—'
                            }}</span>
                            · {{ formatDate(approval.created_at) }}
                        </p>
                        <p
                            v-if="approval.approver"
                            class="mt-0.5 text-sm text-gray-500"
                        >
                            {{
                                approval.status === 'approved'
                                    ? 'Disetujui'
                                    : 'Ditolak'
                            }}
                            oleh
                            <span class="font-medium text-gray-700">{{
                                approval.approver.name
                            }}</span>
                            · {{ formatDate(approval.approved_at) }}
                        </p>
                    </div>
                </div>

                <!-- diff table -->
                <div
                    v-if="diff.length > 0"
                    class="border-b border-gray-100 px-6 py-5"
                >
                    <h3
                        class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400"
                    >
                        Detail Perubahan
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="item in diff"
                            :key="item.field"
                            class="grid grid-cols-3 gap-3 rounded-lg bg-gray-50 p-3 text-sm"
                        >
                            <div>
                                <p class="mb-0.5 text-xs text-gray-400">
                                    Field
                                </p>
                                <p class="font-medium text-gray-700">
                                    {{ item.label }}
                                </p>
                            </div>
                            <div>
                                <p class="mb-0.5 text-xs text-gray-400">
                                    Sebelum
                                </p>
                                <p class="italic text-gray-500">
                                    {{ item.old ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="mb-0.5 text-xs text-gray-400">
                                    Sesudah
                                </p>
                                <p class="font-medium text-green-700">
                                    {{ item.new ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- no diff (create action) -->
                <div
                    v-else-if="approval.action === 'create'"
                    class="border-b border-gray-100 px-6 py-5"
                >
                    <p class="text-sm italic text-gray-500">
                        Penambahan data baru — lihat profil anggota untuk detail
                        lengkap.
                    </p>
                    <Link
                        v-if="approval.approvable?.id"
                        :href="`/people/${approval.approvable.id}`"
                        class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        Lihat profil →
                    </Link>
                </div>

                <!-- rejection reason -->
                <div
                    v-if="approval.rejection_reason"
                    class="border-b border-red-100 bg-red-50 px-6 py-4"
                >
                    <p
                        class="mb-1 text-xs font-semibold uppercase tracking-wider text-red-500"
                    >
                        Alasan Penolakan
                    </p>
                    <p class="text-sm text-red-700">
                        {{ approval.rejection_reason }}
                    </p>
                </div>

                <!-- action buttons -->
                <div
                    v-if="
                        approval.status === 'pending' &&
                        (can.approve || can.reject || can.cancel)
                    "
                    class="flex items-center justify-between gap-3 bg-gray-50 px-6 py-4"
                >
                    <!-- Batalkan pengajuan (pengaju sendiri atau admin) -->
                    <button
                        v-if="can.cancel"
                        @click="cancelApproval"
                        :disabled="cancelProcessing"
                        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-100 disabled:opacity-50"
                    >
                        {{
                            cancelProcessing
                                ? 'Membatalkan...'
                                : 'Batalkan Pengajuan'
                        }}
                    </button>
                    <div v-else />
                    <!-- Approve / Reject (admin / moderator berwenang) -->
                    <div class="flex gap-3">
                        <button
                            v-if="can.reject"
                            @click="showRejectModal = true"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Tolak
                        </button>
                        <button
                            v-if="can.approve"
                            @click="showApproveModal = true"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                        >
                            Setujui
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- approve modal -->
        <Teleport to="body">
            <div
                v-if="showApproveModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <div class="mb-4 flex items-start gap-4">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100"
                        >
                            <svg
                                class="h-5 w-5 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Konfirmasi Persetujuan
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Ketik
                                <span class="font-mono font-bold text-gray-800"
                                    >SETUJU</span
                                >
                                untuk mengkonfirmasi. Tindakan tidak dapat
                                dibatalkan.
                            </p>
                        </div>
                    </div>
                    <input
                        v-model="confirmText"
                        type="text"
                        placeholder="SETUJU"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-green-300"
                    />
                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            @click="showApproveModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitApprove"
                            :disabled="
                                confirmText !== 'SETUJU' ||
                                approveForm.processing
                            "
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:opacity-50"
                        >
                            {{
                                approveForm.processing
                                    ? 'Menyetujui...'
                                    : 'Setujui'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- reject modal -->
        <Teleport to="body">
            <div
                v-if="showRejectModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <div class="mb-4 flex items-start gap-4">
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Tolak Permintaan
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Jelaskan alasan penolakan (min. 10 karakter).
                            </p>
                        </div>
                    </div>
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        placeholder="Alasan penolakan..."
                        class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                    />
                    <p class="mt-1 text-xs text-gray-400">
                        {{ rejectReason.length }}/10 karakter minimum
                    </p>
                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            @click="showRejectModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitReject"
                            :disabled="
                                rejectReason.length < 10 ||
                                rejectForm.processing
                            "
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ rejectForm.processing ? 'Menolak...' : 'Tolak' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
