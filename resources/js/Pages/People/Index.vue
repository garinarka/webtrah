<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    people: Object,
    filters: Object,
    can: Object
});

const auth = useAuthStore();
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Debounce search
let timeout;
watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/people',
            value || statusFilter.value
                ? {
                    ...(value && { search: value }),
                    ...(statusFilter.value && { status: statusFilter.value })
                }
                : {},
            {
                preserveState: true,
                replace: true
            }
        );
    }, 300);
});

watch(statusFilter, (value) => {
    router.get('/people',
        search.value || value
            ? {
                ...(search.value && { search: search.value }),
                ...(value && { status: value })
            }
            : {},
        {
            preserveState: true,
            replace: true
        }
    );
});
</script>

<template>

    <Head title="Daftar Anggota" />
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Daftar Anggota</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ people.total }} orang terdaftar</p>
                </div>

                <!-- Add button (permission-aware) -->
                <button v-if="can.create"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    + Tambah Anggota
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4">
                <input v-model="search" type="text" placeholder="Cari nama..."
                    class="block w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <select v-model="statusFilter"
                    class="block w-full sm:w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="pending">Pending</option>
                    <option value="draft">Draft</option>
                </select>
            </div>

            <!-- List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li v-for="person in people.data" :key="person.id">
                        <Link :href="`/people/${person.id}`" class="block hover:bg-gray-50">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <!-- Avatar -->
                                        <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-medium"
                                            :class="{
                                                'bg-blue-100 text-blue-600': person.gender === 'male',
                                                'bg-pink-100 text-pink-600': person.gender === 'female',
                                                'bg-gray-100 text-gray-600': person.gender === 'unknown'
                                            }">
                                            {{ person.display_name.charAt(0) }}
                                        </div>

                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ person.display_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ person.birth_date ? new Date(person.birth_date).getFullYear() : '?'
                                                }}
                                                {{ person.death_date ? '- ' + new Date(person.death_date).getFullYear()
                                                    : '' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status badge -->
                                    <div class="flex items-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-800': person.status === 'active',
                                                'bg-yellow-100 text-yellow-800': person.status === 'pending',
                                                'bg-gray-100 text-gray-800': person.status === 'draft'
                                            }">
                                            {{ person.status }}
                                        </span>

                                        <!-- Edit hint (permission-aware, no button) -->
                                        <span v-if="can.edit" class="ml-2 text-xs text-gray-400">
                                            [dapat edit]
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                <div class="flex justify-between w-full">
                    <Link v-if="people.prev_page_url" :href="people.prev_page_url"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Sebelumnya
                    </Link>
                    <span v-else class="invisible">Sebelumnya</span>

                    <span class="text-sm text-gray-700">
                        Halaman {{ people.current_page }} dari {{ people.last_page }}
                    </span>

                    <Link v-if="people.next_page_url" :href="people.next_page_url"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Selanjutnya
                    </Link>
                    <span v-else class="invisible">Selanjutnya</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
