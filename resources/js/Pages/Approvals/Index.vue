<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    approvals: Object,
    can: Object,
});

const actionLabel = (action) => ({ create: 'Tambah', update: 'Ubah', delete: 'Hapus' }[action] ?? action);
const actionColor = (action) => ({
    create: 'bg-green-100 text-green-700',
    update: 'bg-blue-100 text-blue-700',
    delete: 'bg-red-100 text-red-700',
}[action] ?? 'bg-gray-100 text-gray-600');

const typeLabel = (type) => ({
    'App\\Models\\Person': 'Anggota',
    'App\\Models\\Relationship': 'Relasi',
}[type] ?? 'Data');

const formatDate = (iso) => new Date(iso).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
});
</script>

<template>

    <Head title="Queue Persetujuan" />
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-950">Queue Persetujuan</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ approvals.total }}
                        permintaan menunggu ditinjau
                    </p>
                </div>
            </div>

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

            <!-- empty state -->
            <div v-if="approvals.data.length === 0"
                class="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col items-center justify-center py-16 text-gray-400">
                <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium">Tidak ada antrian persetujuan</p>
                <p class="text-xs mt-1">Semua permintaan sudah ditangani</p>
            </div>

            <!-- list -->
            <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <ul class="divide-y divide-gray-100">
                    <li v-for="approval in approvals.data" :key="approval.id">
                        <Link :href="`/approvals/${approval.id}`"
                            class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition-colors group">

                            <!-- action badge -->
                            <span
                                :class="['px-2.5 py-1 text-xs font-semibold rounded-lg flex-shrink-0', actionColor(approval.action)]">
                                {{ actionLabel(approval.action) }}
                            </span>

                            <!-- info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ approval.approvable?.display_name ?? 'Data Baru' }}
                                    </p>
                                    <span class="text-xs text-gray-400 flex-shrink-0">
                                        · {{ typeLabel(approval.approvable_type) }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    oleh <span class="text-gray-600">{{ approval.requester?.name ?? '—' }}</span>
                                    · {{ formatDate(approval.created_at) }}
                                </p>
                            </div>

                            <!-- chevron -->
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-400 flex-shrink-0 transition-colors"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- pagination -->
            <div v-if="approvals.last_page > 1" class="flex justify-center gap-1">
                <Link v-for="(link, i) in approvals.links" :key="i" :href="link.url ?? '#'" :class="[
                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                    link.active
                        ? 'bg-indigo-600 text-white'
                        : link.url
                            ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                            : 'bg-gray-50 text-gray-300 cursor-not-allowed border border-gray-100',
                ]" v-html="link.label" />
            </div>
        </div>
    </AppLayout>
</template>
