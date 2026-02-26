<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    person: Object,
    can: Object
});
</script>

<template>

    <Head :title="person.display_name" />
    <AppLayout>
        <div class="max-w-3xl mx-auto">
            <!-- Back link -->
            <Link href="/people" class="text-sm text-indigo-600 hover:text-indigo-500">
                ← Kembali ke daftar
            </Link>

            <!-- Profile card -->
            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 flex items-center">
                    <div class="h-20 w-20 rounded-full flex items-center justify-center text-2xl font-bold" :class="{
                        'bg-blue-100 text-blue-600': person.gender === 'male',
                        'bg-pink-100 text-pink-600': person.gender === 'female',
                        'bg-gray-100 text-gray-600': person.gender === 'unknown'
                    }">
                        {{ person.display_name.charAt(0) }}
                    </div>
                    <div class="ml-6">
                        <h3 class="text-2xl font-bold text-gray-900">{{ person.display_name }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Status: <span class="font-medium" :class="{
                                'text-green-600': person.status === 'active',
                                'text-yellow-600': person.status === 'pending'
                            }">{{ person.status }}</span>
                        </p>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">{{ person.gender }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ person.birth_date || 'Tidak diketahui' }}
                                <span v-if="person.birth_accuracy !== 'exact'" class="text-gray-400">
                                    ({{ person.birth_accuracy }})
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Meninggal</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ person.death_date || '-' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dibuat Oleh</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ person.creator?.name || 'System' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions (permission-aware) -->
                <div v-if="can.edit || can.delete" class="border-t border-gray-200 px-4 py-4 sm:px-6">
                    <div class="flex gap-3">
                        <button v-if="can.edit"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Edit (Coming Phase 4)
                        </button>
                        <button v-if="can.delete"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
