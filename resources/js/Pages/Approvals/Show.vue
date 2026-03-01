<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    approval: Object,
    diff: Array,
    can: Object,
});

// APPROVE
const showApproveModal = ref(false);
const confirmText = ref('');

const approveForm = useForm({ confirmation: '' });
const submitApprove = () => {
    approveForm.confirmation = confirmText.value;
    approveForm.post(`/approvals/${props.approval.id}/approve`, {
        onSuccess: () => { showApproveModal.value = false; },
    });
};

// REJECT
const showRejectModal = ref(false);
const rejectReason = ref('');

const rejectForm = useForm({ reason: '' });
const submitReject = () => {
    rejectForm.reason = rejectReason.value;
    rejectForm.post(`/approvals/${props.approval.id}/reject`, {
        onSuccess: () => { showRejectModal.value = false; },
    });
};

// HELPERS
const actionLabel = (a) => ({ create: 'Penambahan', update: 'Perubahan', delete: 'Penghapusan' }[a] ?? a);
const actionColor = (a) => ({
    create: 'bg-green-100 text-green-700 ring-green-600/20',
    update: 'bg-blue-100 text-blue-700 ring-blue-600/20',
    delete: 'bg-red-100 text-red-700 ring-red-600/20',
}[a] ?? 'bg-gray-100 text-gray-600');

const statusConfig = (s) => ({
    pending: { label: 'Menunggu', cls: 'bg-amber-50 text-amber-700 ring-amber-600/20' },
    approved: { label: 'Disetujui', cls: 'bg-green-50 text-green-700 ring-green-600/20' },
    rejected: { label: 'Ditolak', cls: 'bg-red-50 text-red-700 ring-red-600/20' },
}[s] ?? { label: s, cls: 'bg-gray-50 text-gray-600' });

const formatDate = (iso) => new Date(iso).toLocaleString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
});

const typeLabel = (type) => ({
    'App\\Models\\Person': 'Anggota',
    'App\\Models\\Relationship': 'Relasi',
}[type] ?? 'Data');
</script>

<template>

    <Head title="Detail Persetujuan" />
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-6">

            <!-- breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link href="/approvals" class="hover:text-indigo-600 transition-colors">Queue Persetujuan</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-800 font-medium">Detail</span>
            </nav>

            <!-- flash -->
            <div v-if="$page.props.flash?.message"
                class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                {{ $page.props.flash.message }}
            </div>

            <!-- main card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                <!-- header -->
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span
                                :class="['px-2.5 py-1 text-xs font-semibold rounded-lg ring-1 ring-inset', actionColor(approval.action)]">
                                {{ actionLabel(approval.action) }}
                            </span>
                            <span class="text-sm text-gray-500">{{ typeLabel(approval.approvable_type) }}</span>
                        </div>
                        <span
                            :class="['px-2.5 py-1 text-xs font-semibold rounded-lg ring-1 ring-inset flex-shrink-0', statusConfig(approval.status).cls]">
                            {{ statusConfig(approval.status).label }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ approval.approvable?.display_name ?? 'Data Baru' }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Diajukan oleh
                            <span class="font-medium text-gray-700">{{ approval.requester?.name ?? '—' }}</span>
                            · {{ formatDate(approval.created_at) }}
                        </p>
                        <p v-if="approval.approver" class="text-sm text-gray-500 mt-0.5">
                            {{ approval.status === 'approved' ? 'Disetujui' : 'Ditolak' }} oleh
                            <span class="font-medium text-gray-700">{{ approval.approver.name }}</span>
                            · {{ formatDate(approval.approved_at) }}
                        </p>
                    </div>
                </div>

                <!-- diff table -->
                <div v-if="diff.length > 0" class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Detail Perubahan</h3>
                    <div class="space-y-2">
                        <div v-for="item in diff" :key="item.field"
                            class="grid grid-cols-3 gap-3 p-3 bg-gray-50 rounded-lg text-sm">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Field</p>
                                <p class="font-medium text-gray-700">{{ item.label }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Sebelum</p>
                                <p class="text-gray-500 italic">{{ item.old ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Sesudah</p>
                                <p class="font-medium text-green-700">{{ item.new ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- no diff (create action) -->
                <div v-else-if="approval.action === 'create'" class="px-6 py-5 border-b border-gray-100">
                    <p class="text-sm text-gray-500 italic">Penambahan data baru — lihat profil anggota untuk detail
                        lengkap.</p>
                    <Link v-if="approval.approvable?.id" :href="`/people/${approval.approvable.id}`"
                        class="text-sm text-indigo-600 hover:text-indigo-800 mt-2 inline-block">
                        Lihat profil →
                    </Link>
                </div>

                <!-- rejection reason -->
                <div v-if="approval.rejection_reason" class="px-6 py-4 bg-red-50 border-b border-red-100">
                    <p class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Alasan Penolakan</p>
                    <p class="text-sm text-red-700">{{ approval.rejection_reason }}</p>
                </div>

                <!-- action buttons -->
                <div v-if="approval.status === 'pending' && (can.approve || can.reject)"
                    class="px-6 py-4 bg-gray-50 flex gap-3 justify-end">
                    <button v-if="can.reject" @click="showRejectModal = true"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Tolak
                    </button>
                    <button v-if="can.approve" @click="showApproveModal = true"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                        Setujui
                    </button>
                </div>
            </div>
        </div>

        <!-- approve modal -->
        <Teleport to="body">
            <div v-if="showApproveModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Konfirmasi Persetujuan</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Ketik <span class="font-mono font-bold text-gray-800">SETUJU</span> untuk
                                mengkonfirmasi.
                                Tindakan tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>
                    <input v-model="confirmText" type="text" placeholder="SETUJU"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-300 font-mono" />
                    <div class="flex gap-3 mt-4 justify-end">
                        <button @click="showApproveModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button @click="submitApprove" :disabled="confirmText !== 'SETUJU' || approveForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-50 transition-colors">
                            {{ approveForm.processing ? 'Menyetujui...' : 'Setujui' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- reject modal -->
        <Teleport to="body">
            <div v-if="showRejectModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Tolak Permintaan</h3>
                            <p class="text-sm text-gray-500 mt-1">Jelaskan alasan penolakan (min. 10 karakter).</p>
                        </div>
                    </div>
                    <textarea v-model="rejectReason" rows="3" placeholder="Alasan penolakan..."
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300 resize-none" />
                    <p class="text-xs text-gray-400 mt-1">{{ rejectReason.length }}/10 karakter minimum</p>
                    <div class="flex gap-3 mt-4 justify-end">
                        <button @click="showRejectModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button @click="submitReject" :disabled="rejectReason.length < 10 || rejectForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg disabled:opacity-50 transition-colors">
                            {{ rejectForm.processing ? 'Menolak...' : 'Tolak' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
