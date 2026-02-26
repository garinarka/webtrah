<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    approvals: Object,
    can: Object
});

const actionLabel = (action) => ({
    'create': 'Tambah',
    'update': 'Ubah',
    'delete': 'Hapus'
}[action] || action);

const typeLabel = (type) => ({
    'App\\Models\\Person': 'Anggota',
    'App\\Models\\Relationship': 'Relasi',
    'App\\Models\\Event': 'Peristiwa'
}[type] || 'Data');
</script>

<template>

    <Head title="Persetujuan" />
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Queue Persetujuan</h1>
                <p class="mt-1 text-sm text-gray-500">{{ approvals.total }} menunggu persetujuan</p>
            </div>

            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li v-for="approval in approvals.data" :key="approval.id">
                        <Link :href="`/approvals/${approval.id}`" class="block hover:bg-gray-50">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">
                                                {{ actionLabel(approval.action) }}
                                            </span>
                                            <span class="ml-2 text-sm text-gray-500">
                                                {{ typeLabel(approval.approvable_type) }}
                                            </span>
                                        </div>
                                        <div class="mt-2 text-sm font-medium text-gray-900">
                                            {{ approval.approvable?.display_name || 'Data baru' }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Oleh: {{ approval.requester?.name }} • {{ new
                                                Date(approval.created_at).toLocaleDateString('id-ID') }}
                                        </div>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- Pagination -->
            <div v-if="approvals.links.length > 3" class="flex justify-center">
                <div class="flex space-x-2">
                    <Link v-for="(link, index) in approvals.links" :key="index" :href="link.url" :class="[
                        'px-3 py-1 rounded text-sm',
                        link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                        !link.url && 'opacity-50 cursor-not-allowed'
                    ]" v-html="link.label" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
