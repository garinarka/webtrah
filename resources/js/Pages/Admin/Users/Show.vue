<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    member: Object,
    contributions: Object,
    approvalHistory: Array,
    availableRoles: Array,
    can: Object,
});

// CHANGE ROLE
const showRoleModal = ref(false);
const selectedRole = ref(props.member.role);
const roleProcessing = ref(false);

const submitRoleChange = () => {
    if (selectedRole.value === props.member.role) { showRoleModal.value = false; return; }
    roleProcessing.value = true;
    router.patch(`/admin/users/${props.member.id}/role`, { role: selectedRole.value }, {
        onSuccess: () => { showRoleModal.value = false; },
        onFinish: () => { roleProcessing.value = false; },
    });
};

// RESET PASSWORD
const showResetModal = ref(false);
const resetProcessing = ref(false);

const submitReset = () => {
    resetProcessing.value = true;
    router.post(`/admin/users/${props.member.id}/reset-password`, {}, {
        onSuccess: () => { showResetModal.value = false; },
        onFinish: () => { resetProcessing.value = false; },
    });
};

// HELPERS
const roleConfig = (r) => ({
    admin: { label: 'Admin', cls: 'bg-red-100 text-red-700' },
    moderator: { label: 'Moderator', cls: 'bg-amber-100 text-amber-700' },
    user: { label: 'Anggota', cls: 'bg-gray-100 text-gray-600' },
}[r] ?? { label: r ?? '—', cls: 'bg-gray-100 text-gray-600' });

const approvalStatusColor = (s) => ({
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
}[s] ?? 'bg-gray-100 text-gray-600');

const approvalActionLabel = (a) => ({ create: 'Tambah', update: 'Ubah', delete: 'Hapus' }[a] ?? a);

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
    : '—';

const roleDescriptions = {
    admin: 'Akses penuh: kelola semua data, user, dan persetujuan.',
    moderator: 'Kelola data anggota dan tinjau persetujuan.',
    user: 'Lihat data dan ajukan perubahan untuk ditinjau.',
};

const isSelf = props.member.id === /* injected from Inertia */ (window?.__INERTIA_PAGE__?.props?.auth?.user?.id);
</script>

<template>

    <Head :title="`Pengguna — ${member.name}`" />
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-6">

            <!-- breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <Link href="/admin/users" class="hover:text-indigo-600 transition-colors">Manajemen Pengguna</Link>
                <span class="text-gray-300">/</span>
                <span class="text-gray-800 font-medium">{{ member.name }}</span>
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

            <!-- profile card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- header gradient -->
                <div class="px-6 py-5 bg-gradient-to-br from-slate-700 to-slate-600 flex items-center gap-4">
                    <div
                        class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-2xl font-bold text-white flex-shrink-0">
                        {{ member.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">{{ member.name }}</h1>
                        <p class="text-slate-300 text-sm mt-0.5">{{ member.email }}</p>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span
                                :class="['px-2 py-0.5 text-xs font-semibold rounded-md ring-1 ring-white/20', roleConfig(member.role).cls]">
                                {{ roleConfig(member.role).label }}
                            </span>
                            <span v-if="member.email_verified_at" class="text-slate-300 text-xs">✓ Terverifikasi</span>
                            <span v-else class="text-amber-300 text-xs">⚠ Belum terverifikasi</span>
                        </div>
                    </div>
                </div>

                <!-- detail fields -->
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tanggal Bergabung</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(member.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Email Diverifikasi</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(member.email_verified_at) }}</dd>
                    </div>
                </div>

                <!-- actions -->
                <div v-if="can.change_role || can.suspend" class="px-6 pb-5 flex flex-wrap gap-2">
                    <button v-if="can.change_role" @click="showRoleModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                        Ubah Role
                    </button>
                    <button @click="showResetModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                        </svg>
                        Reset Password
                    </button>
                </div>
            </div>

            <!-- contribution stats -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    Statistik Kontribusi
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div v-for="(val, key) in {
                        'Total Diajukan': contributions.total_submitted,
                        'Aktif': contributions.active,
                        'Menunggu': contributions.pending,
                        'Draft': contributions.draft,
                    }" :key="key" class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-xl font-bold text-gray-900">{{ val }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ key }}</p>
                    </div>
                </div>
            </div>

            <!-- approval history -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Riwayat Permintaan
                    <span class="text-xs text-gray-400 font-normal">(10 terakhir)</span>
                </h3>
                <div v-if="approvalHistory.length === 0" class="text-sm text-gray-400 italic py-4 text-center">
                    Belum ada riwayat permintaan.
                </div>
                <div v-else class="space-y-2">
                    <Link v-for="item in approvalHistory" :key="item.id" :href="`/approvals/${item.id}`"
                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-gray-100 text-gray-600 flex-shrink-0">
                            {{ approvalActionLabel(item.action) }}
                        </span>
                        <span class="text-sm text-gray-800 flex-1 truncate">{{ item.person_name }}</span>
                        <span
                            :class="['px-2 py-0.5 text-xs font-semibold rounded flex-shrink-0', approvalStatusColor(item.status)]">
                            {{ item.status === 'pending' ? 'Menunggu' : item.status === 'approved' ? 'Disetujui' :
                                'Ditolak' }}
                        </span>
                        <span class="text-xs text-gray-400 flex-shrink-0 hidden sm:block">
                            {{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
                            }}
                        </span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- change role modal -->
        <Teleport to="body">
            <div v-if="showRoleModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Ubah Role Pengguna</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Ubah role untuk <span class="font-medium text-gray-700">{{ member.name }}</span>
                    </p>

                    <!-- role options -->
                    <div class="space-y-2 mb-5">
                        <label v-for="r in availableRoles" :key="r" :class="[
                            'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors',
                            selectedRole === r
                                ? 'border-indigo-500 bg-indigo-50'
                                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50',
                        ]">
                            <input type="radio" :value="r" v-model="selectedRole"
                                class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ r.charAt(0).toUpperCase() + r.slice(1) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ roleDescriptions[r] }}</p>
                            </div>
                        </label>
                    </div>

                    <div v-if="selectedRole !== member.role"
                        class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700 mb-4">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        Role akan diubah dari <strong>{{ member.role }}</strong> menjadi <strong>{{ selectedRole
                        }}</strong>
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button @click="showRoleModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button @click="submitRoleChange" :disabled="roleProcessing || selectedRole === member.role"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50 transition-colors">
                            {{ roleProcessing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- reset password modal -->
        <Teleport to="body">
            <div v-if="showResetModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Reset Password</h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                Link reset password akan dikirim ke email
                                <span class="font-medium text-gray-700">{{ member.email }}</span>.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button @click="showResetModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button @click="submitReset" :disabled="resetProcessing"
                            class="px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg disabled:opacity-50 transition-colors">
                            {{ resetProcessing ? 'Mengirim...' : 'Kirim Link Reset' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
