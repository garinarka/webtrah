<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    users: Object,
    stats: Object,
    filters: Object,
    roles: Array,
});

// FILTERS
const search = ref(props.filters?.search ?? '');
const role = ref(props.filters?.role ?? '');
let searchTimer;

const applyFilters = () => {
    router.get('/admin/users', {
        search: search.value || undefined,
        role: role.value || undefined,
    }, { preserveState: true, replace: true });
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch(role, applyFilters);

// HELPERS
const roleConfig = (r) => ({
    admin: { label: 'Admin', cls: 'bg-red-100 text-red-700' },
    moderator: { label: 'Moderator', cls: 'bg-amber-100 text-amber-700' },
    user: { label: 'Anggota', cls: 'bg-gray-100 text-gray-600' },
}[r] ?? { label: r, cls: 'bg-gray-100 text-gray-600' });

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '—';
</script>

<template>

    <Head title="Manajemen Pengguna" />
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">

            <!-- header -->
            <div>
                <h1 class="text-xl font-semibold text-gray-950">Manajemen Pengguna</h1>
                <p class="text-sm text-gray-500 mt-0.5">Kelola role dan akses semua pengguna</p>
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

            <!-- stat cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="(val, key) in { 'Total': stats.total, 'Admin': stats.admins, 'Moderator': stats.moderators, 'Anggota': stats.members }"
                    :key="key" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ val }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ key }}</p>
                </div>
            </div>

            <!-- filter bar -->
            <div class="flex flex-wrap gap-3">
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-5.197-5.197A7.5 7.5 0 105.196 5.196 7.5 7.5 0 0016.803 15.803z" />
                    </svg>
                    <input v-model="search" type="text" placeholder="Cari nama atau email..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                </div>
                <select v-model="role"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    <option value="">Semua Role</option>
                    <option v-for="r in roles" :key="r" :value="r">
                        {{ r.charAt(0).toUpperCase() + r.slice(1) }}
                    </option>
                </select>
            </div>

            <!-- table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- empty -->
                <div v-if="users.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-gray-400">
                    <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <p class="text-sm">Tidak ada pengguna ditemukan</p>
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-gray-100 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            <th class="px-5 py-3 text-left">Pengguna</th>
                            <th class="px-5 py-3 text-left hidden sm:table-cell">Role</th>
                            <th class="px-5 py-3 text-left hidden md:table-cell">Bergabung</th>
                            <th class="px-5 py-3 text-left hidden md:table-cell">Verifikasi</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-gray-50 transition-colors group">
                            <!-- avatar + name + email -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-semibold text-sm flex items-center justify-center flex-shrink-0">
                                        {{ u.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ u.name }}</p>
                                        <p class="text-xs text-gray-400">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <!-- role badge -->
                            <td class="px-5 py-3.5 hidden sm:table-cell">
                                <span
                                    :class="['px-2 py-0.5 text-xs font-semibold rounded-lg', roleConfig(u.roles[0]?.name).cls]">
                                    {{ roleConfig(u.roles[0]?.name).label }}
                                </span>
                            </td>
                            <!-- join date -->
                            <td class="px-5 py-3.5 hidden md:table-cell text-gray-500">
                                {{ formatDate(u.created_at) }}
                            </td>
                            <!-- verified -->
                            <td class="px-5 py-3.5 hidden md:table-cell">
                                <span v-if="u.email_verified_at"
                                    class="inline-flex items-center gap-1 text-xs text-green-600">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Terverifikasi
                                </span>
                                <span v-else class="text-xs text-gray-400 italic">Belum</span>
                            </td>
                            <!-- actions -->
                            <td class="px-5 py-3.5 text-right">
                                <Link :href="`/admin/users/${u.id}`"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                    Detail →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- pagination -->
            <div v-if="users.last_page > 1" class="flex justify-center gap-1">
                <Link v-for="(link, i) in users.links" :key="i" :href="link.url ?? '#'" :class="[
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
