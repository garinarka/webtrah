<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    approval: Object,
    diff: Array,
    can: Object
});

const showApproveConfirm = ref(false);
const showRejectForm = ref(false);
const confirmationText = ref('');
const rejectReason = ref('');

const approveForm = useForm({
    confirmation: ''
});

const rejectForm = useForm({
    reason: ''
});

const submitApprove = () => {
    approveForm.confirmation = confirmationText.value;
    approveForm.post(`/approvals/${props.approval.id}/approve`, {
        onSuccess: () => showApproveConfirm.value = false
    });
};

const submitReject = () => {
    rejectForm.reason = rejectReason.value;
    rejectForm.post(`/approvals/${props.approval.id}/reject`, {
        onSuccess: () => showRejectForm.value = false
    });
};
</script>

<template>

    <Head title="Detail Persetujuan" />
    <AdminLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Back -->
            <a href="/approvals" class="text-sm text-indigo-600 hover:text-indigo-500">← Kembali ke queue</a>

            <!-- Header -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Permintaan {{ approval.action === 'create' ? 'Penambahan' : approval.action === 'update'
                                    ? 'Perubahan' : 'Penghapusan' }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Diajukan oleh {{ approval.requester?.name }} pada {{ new
                                    Date(approval.created_at).toLocaleString('id-ID') }}
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                            {{ approval.status }}
                        </span>
                    </div>
                </div>

                <!-- Diff View -->
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Perubahan</h4>

                    <div class="space-y-4">
                        <div v-for="item in diff" :key="item.field"
                            class="grid grid-cols-3 gap-4 p-3 bg-gray-50 rounded-md">
                            <div>
                                <span class="text-xs text-gray-500">Field</span>
                                <p class="text-sm font-medium text-gray-900">{{ item.label }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Sebelum</span>
                                <p class="text-sm text-gray-900">{{ item.old || '-' }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Sesudah</span>
                                <p class="text-sm font-medium text-green-700">{{ item.new }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div v-if="approval.status === 'pending' && (can.approve || can.reject)"
                    class="border-t border-gray-200 px-4 py-4 sm:px-6">
                    <div class="flex gap-3">
                        <button v-if="can.reject" @click="showRejectForm = true"
                            class="flex-1 px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Tolak
                        </button>
                        <button v-if="can.approve" @click="showApproveConfirm = true"
                            class="flex-1 px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Setujui
                        </button>
                    </div>
                </div>
            </div>

            <!-- Approve Confirmation Modal -->
            <div v-if="showApproveConfirm"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900">Konfirmasi Persetujuan</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Ketik "SETUJU" untuk mengkonfirmasi perubahan ini. Tindakan tidak dapat dibatalkan.
                    </p>
                    <input v-model="confirmationText" type="text" placeholder="SETUJU"
                        class="mt-4 block w-full rounded-md border-gray-300 shadow-sm" />
                    <div class="mt-4 flex gap-3">
                        <button @click="showApproveConfirm = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                            Batal
                        </button>
                        <button @click="submitApprove"
                            :disabled="confirmationText !== 'SETUJU' || approveForm.processing"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                            Konfirmasi
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reject Form Modal -->
            <div v-if="showRejectForm"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900">Alasan Penolakan</h3>
                    <textarea v-model="rejectReason" rows="3" placeholder="Jelaskan mengapa perubahan ini ditolak..."
                        class="mt-4 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    <div class="mt-4 flex gap-3">
                        <button @click="showRejectForm = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                            Batal
                        </button>
                        <button @click="submitReject" :disabled="rejectReason.length < 10 || rejectForm.processing"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50">
                            Tolak Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
