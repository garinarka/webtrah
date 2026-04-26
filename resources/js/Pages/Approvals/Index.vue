<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    approvals: Object,
    can: Object,
})

const actionLabel = (action) =>
    ({
        create: 'Tambah',
        update: 'Ubah',
        delete: 'Hapus',
        delete_relation: 'Hapus Relasi',
        resign_moderator: 'Pengunduran Diri',
    })[action] ?? action
const actionColor = (action) =>
    ({
        create: 'bg-green-100 text-green-700',
        update: 'bg-blue-100 text-blue-700',
        delete: 'bg-red-100 text-red-700',
        delete_relation: 'bg-orange-100 text-orange-700',
        resign_moderator: 'bg-purple-100 text-purple-700',
    })[action] ?? 'bg-gray-100 text-gray-600'

const typeLabel = (type) =>
    ({
        'App\\Models\\Person': 'Anggota',
        'App\\Models\\Relationship': 'Relasi',
    })[type] ?? (type ? 'Data' : 'Moderator')

const formatDate = (iso) =>
    new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
</script>

<template>
    <Head title="Queue Persetujuan" />
    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-950">
                        Queue Persetujuan
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ approvals.total }}
                        permintaan menunggu ditinjau
                    </p>
                </div>
            </div>

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

            <!-- empty state -->
            <div
                v-if="approvals.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-16 text-gray-400 shadow-sm"
            >
                <svg
                    class="mb-3 h-12 w-12"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <p class="text-sm font-medium">Tidak ada antrian persetujuan</p>
                <p class="mt-1 text-xs">Semua permintaan sudah ditangani</p>
            </div>

            <!-- list -->
            <div
                v-else
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <ul class="divide-y divide-gray-100">
                    <li v-for="approval in approvals.data" :key="approval.id">
                        <Link
                            :href="`/approvals/${approval.id}`"
                            class="group flex items-center gap-4 px-5 py-4 transition-colors hover:bg-gray-50"
                        >
                            <!-- action badge -->
                            <span
                                :class="[
                                    'flex-shrink-0 rounded-lg px-2.5 py-1 text-xs font-semibold',
                                    actionColor(approval.action),
                                ]"
                            >
                                {{ actionLabel(approval.action) }}
                            </span>

                            <!-- info -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p
                                        class="truncate text-sm font-medium text-gray-900"
                                    >
                                        {{
                                            approval.approvable?.display_name ??
                                            'Data Baru'
                                        }}
                                    </p>
                                    <span
                                        class="flex-shrink-0 text-xs text-gray-400"
                                    >
                                        ·
                                        {{
                                            typeLabel(approval.approvable_type)
                                        }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    oleh
                                    <span class="text-gray-600">{{
                                        approval.requester?.name ?? '—'
                                    }}</span>
                                    · {{ formatDate(approval.created_at) }}
                                </p>
                            </div>

                            <!-- chevron -->
                            <svg
                                class="h-4 w-4 flex-shrink-0 text-gray-300 transition-colors group-hover:text-gray-400"
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
                    </li>
                </ul>
            </div>

            <!-- pagination -->
            <div
                v-if="approvals.last_page > 1"
                class="flex justify-center gap-1"
            >
                <Link
                    v-for="(link, i) in approvals.links"
                    :key="i"
                    :href="link.url ?? '#'"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        link.active
                            ? 'bg-indigo-600 text-white'
                            : link.url
                              ? 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-100'
                              : 'cursor-not-allowed border border-gray-100 bg-gray-50 text-gray-300',
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
