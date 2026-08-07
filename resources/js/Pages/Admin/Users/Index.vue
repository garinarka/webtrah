<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
    users: Object,
    stats: Object,
    filters: Object,
    roles: Array,
})

// ── FILTER ──────────────────────────────────────────────────────────────────
const search = ref(props.filters?.search ?? '')
const role = ref(props.filters?.role ?? '')
let searchTimer

const applyFilters = () => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role: role.value || undefined,
        },
        { preserveState: true, replace: true },
    )
}

watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 400)
})
watch(role, applyFilters)

// ── HELPERS ──────────────────────────────────────────────────────────────────
const roleConfig = (r) =>
    ({
        admin: { label: 'Admin', cls: 'bg-red-100 text-red-700' },
        moderator: { label: 'Moderator', cls: 'bg-amber-100 text-amber-700' },
        user: { label: 'Anggota', cls: 'bg-gray-100 text-gray-600' },
    })[r] ?? { label: r, cls: 'bg-gray-100 text-gray-600' }

const formatDate = (iso) =>
    iso
        ? new Date(iso).toLocaleDateString('id-ID', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : '—'

const genderLabel = (g) => (g === 'male' ? 'L' : g === 'female' ? 'P' : '?')
const genderColor = (g) =>
    g === 'male'
        ? 'text-blue-500'
        : g === 'female'
          ? 'text-pink-500'
          : 'text-gray-400'

// ── MODAL: TAMBAH USER DARI DATA ANGGOTA ────────────────────────────────────
const showModal = ref(false)

// Autocomplete search person
const personSearch = ref('')
const personResults = ref([])
const personLoading = ref(false)
let personTimer

const searchPersons = async () => {
    if (personSearch.value.length < 2) {
        personResults.value = []
        return
    }
    personLoading.value = true
    try {
        const res = await axios.get('/admin/people-without-users', {
            params: { search: personSearch.value },
        })
        personResults.value = res.data
    } catch {
        personResults.value = []
    } finally {
        personLoading.value = false
    }
}

watch(personSearch, () => {
    clearTimeout(personTimer)
    personTimer = setTimeout(searchPersons, 350)
})

// Form state
const form = useForm({ person_id: '', email: '', role: 'user' })
const selectedPerson = ref(null)

const selectPerson = (p) => {
    selectedPerson.value = p
    form.person_id = p.id
    personSearch.value = p.display_name
    personResults.value = []
}

const closeModal = () => {
    showModal.value = false
    form.reset()
    form.clearErrors()
    selectedPerson.value = null
    personSearch.value = ''
    personResults.value = []
}

const submit = () => {
    form.post('/admin/users/from-person', {
        onSuccess: closeModal,
    })
}
</script>

<template>
    <Head title="Manajemen Pengguna" />
    <AppLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- header -->
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-950">
                        Manajemen Pengguna
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        Kelola role dan akses semua pengguna
                    </p>
                </div>
                <button
                    @click="showModal = true"
                    class="inline-flex flex-shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                        />
                    </svg>
                    Tambah dari Anggota
                </button>
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

            <!-- stat cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    v-for="(val, key) in {
                        Total: stats.total,
                        Admin: stats.admins,
                        Moderator: stats.moderators,
                        Anggota: stats.members,
                    }"
                    :key="key"
                    class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm"
                >
                    <p class="text-2xl font-bold text-gray-900">{{ val }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ key }}</p>
                </div>
            </div>

            <!-- filter bar -->
            <div class="flex flex-wrap gap-3">
                <div class="relative min-w-[180px] flex-1">
                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-5.197-5.197A7.5 7.5 0 105.196 5.196 7.5 7.5 0 0016.803 15.803z"
                        />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama atau email..."
                        class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                    />
                </div>
                <select
                    v-model="role"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                >
                    <option value="">Semua Role</option>
                    <option v-for="r in roles" :key="r" :value="r">
                        {{ r.charAt(0).toUpperCase() + r.slice(1) }}
                    </option>
                </select>
            </div>

            <!-- table -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <div
                    v-if="users.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-gray-400"
                >
                    <svg
                        class="mb-2 h-10 w-10"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>
                    <p class="text-sm">Tidak ada pengguna ditemukan</p>
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-400"
                        >
                            <th class="px-5 py-3 text-left">Pengguna</th>
                            <th
                                class="hidden px-5 py-3 text-left sm:table-cell"
                            >
                                Role
                            </th>
                            <th
                                class="hidden px-5 py-3 text-left md:table-cell"
                            >
                                Bergabung
                            </th>
                            <th
                                class="hidden px-5 py-3 text-left md:table-cell"
                            >
                                Verifikasi
                            </th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="u in users.data"
                            :key="u.id"
                            class="group transition-colors hover:bg-gray-50"
                        >
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700"
                                    >
                                        {{ u.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ u.name }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ u.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span
                                    :class="[
                                        'rounded-lg px-2 py-0.5 text-xs font-semibold',
                                        roleConfig(u.roles[0]?.name).cls,
                                    ]"
                                >
                                    {{ roleConfig(u.roles[0]?.name).label }}
                                </span>
                            </td>
                            <td
                                class="hidden px-5 py-3.5 text-gray-500 md:table-cell"
                            >
                                {{ formatDate(u.created_at) }}
                            </td>
                            <td class="hidden px-5 py-3.5 md:table-cell">
                                <span
                                    v-if="u.email_verified_at"
                                    class="inline-flex items-center gap-1 text-xs text-green-600"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    Terverifikasi
                                </span>
                                <span
                                    v-else
                                    class="text-xs italic text-gray-400"
                                    >Belum</span
                                >
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <Link
                                    :href="`/admin/users/${u.id}`"
                                    class="text-xs font-medium text-indigo-600 transition-colors hover:text-indigo-800"
                                >
                                    Detail →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- pagination -->
            <div v-if="users.last_page > 1" class="flex justify-center gap-1">
                <Link
                    v-for="(link, i) in users.links"
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

        <!-- ── MODAL: Tambah User dari Data Anggota ── -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl"
                >
                    <!-- header -->
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-6 py-4"
                    >
                        <h3 class="text-base font-semibold text-gray-900">
                            Tambah User dari Data Anggota
                        </h3>
                        <button
                            @click="closeModal"
                            class="text-gray-400 transition-colors hover:text-gray-600"
                        >
                            <svg
                                class="h-5 w-5"
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
                        </button>
                    </div>

                    <!-- body -->
                    <div class="space-y-4 px-6 py-5">
                        <!-- error person_id -->
                        <div
                            v-if="form.errors.person_id"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                        >
                            {{ form.errors.person_id }}
                        </div>

                        <!-- cari anggota -->
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Data Anggota <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="personSearch"
                                    type="text"
                                    placeholder="Ketik nama anggota (min. 2 karakter)..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-300"
                                    :class="{
                                        'border-red-300': form.errors.person_id,
                                    }"
                                />

                                <!-- loading -->
                                <div
                                    v-if="personLoading"
                                    class="absolute right-3 top-1/2 -translate-y-1/2"
                                >
                                    <svg
                                        class="h-4 w-4 animate-spin text-gray-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        />
                                    </svg>
                                </div>

                                <!-- dropdown hasil -->
                                <div
                                    v-if="personResults.length > 0"
                                    class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
                                >
                                    <button
                                        v-for="p in personResults"
                                        :key="p.id"
                                        type="button"
                                        @click="selectPerson(p)"
                                        class="flex w-full items-center gap-3 border-b border-gray-50 px-4 py-2.5 text-left transition-colors last:border-0 hover:bg-gray-50"
                                    >
                                        <span
                                            :class="[
                                                'w-4 text-sm font-bold',
                                                genderColor(p.gender),
                                            ]"
                                        >
                                            {{ genderLabel(p.gender) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm text-gray-900"
                                            >
                                                {{ p.display_name }}
                                            </p>
                                            <p
                                                v-if="p.birth_date"
                                                class="text-xs text-gray-400"
                                            >
                                                Lahir {{ p.birth_date }}
                                            </p>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- konfirmasi person terpilih -->
                            <div
                                v-if="selectedPerson"
                                class="mt-2 flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2"
                            >
                                <svg
                                    class="h-4 w-4 flex-shrink-0 text-indigo-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span
                                    class="truncate text-sm font-medium text-indigo-800"
                                >
                                    {{ selectedPerson.display_name }}
                                </span>
                                <button
                                    type="button"
                                    @click="selectedPerson = null; form.person_id = ''; personSearch = ''"
                                    class="ml-auto flex-shrink-0 text-indigo-400 hover:text-indigo-600"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
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
                                </button>
                            </div>
                        </div>

                        <!-- email -->
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Email Login <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="email@contoh.com"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-300"
                                :class="{ 'border-red-300': form.errors.email }"
                            />
                            <p
                                v-if="form.errors.email"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- role -->
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Role</label
                            >
                            <select
                                v-model="form.role"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-300"
                            >
                                <option value="user">Anggota (user)</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Admin</option>
                            </select>
                            <p
                                v-if="form.errors.role"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.role }}
                            </p>
                        </div>

                        <p class="text-xs text-gray-400">
                            Nama akun diambil dari data anggota. Link atur
                            password dikirim otomatis ke email yang didaftarkan.
                        </p>
                    </div>

                    <!-- footer -->
                    <div
                        class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4"
                    >
                        <button
                            @click="closeModal"
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submit"
                            type="button"
                            :disabled="
                                form.processing ||
                                !form.person_id ||
                                !form.email
                            "
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Memproses...' : 'Buat Akun' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
