<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
})

const user = usePage().props.auth.user

// Form info profil
const profileForm = useForm({
    name: user.name,
    email: user.email,
})
const updateProfile = () => {
    profileForm.patch(route('profile.update'), { preserveScroll: true })
}

// Form ganti password
const passwordInput = ref(null)
const currentPasswordInput = ref(null)
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})
const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation')
                passwordInput.value?.focus()
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password')
                currentPasswordInput.value?.focus()
            }
        },
    })
}

// Hapus akun
const deleteForm = useForm({ password: '' })
const showDeleteConfirm = ref(false)
const confirmDelete = () => {
    showDeleteConfirm.value = true
}
const deleteAccount = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onError: () => (showDeleteConfirm.value = true),
        onFinish: () => deleteForm.reset(),
    })
}
</script>

<template>
    <Head title="Profil Saya" />
    <AppLayout>
        <div class="mx-auto max-w-2xl space-y-6 p-4">
            <h1 class="text-2xl font-bold text-gray-900">Profil Saya</h1>

            <!-- Info Profil -->
            <section class="rounded-lg bg-white p-6 shadow">
                <h2 class="text-lg font-medium text-gray-900">
                    Informasi Profil
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Update nama dan email akun kamu.
                </p>

                <form @submit.prevent="updateProfile" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Nama</label
                        >
                        <input
                            v-model="profileForm.name"
                            type="text"
                            autocomplete="name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        />
                        <p
                            v-if="profileForm.errors.name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ profileForm.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Email</label
                        >
                        <input
                            v-model="profileForm.email"
                            type="email"
                            autocomplete="username"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        />
                        <p
                            v-if="profileForm.errors.email"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ profileForm.errors.email }}
                        </p>
                    </div>

                    <div
                        v-if="
                            mustVerifyEmail && user.email_verified_at === null
                        "
                        class="text-sm text-gray-800"
                    >
                        Email kamu belum diverifikasi.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="text-gray-600 underline hover:text-gray-900"
                        >
                            Kirim ulang email verifikasi.
                        </Link>
                        <p
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 font-medium text-green-600"
                        >
                            Link verifikasi baru sudah dikirim.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button
                            type="submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                            :disabled="profileForm.processing"
                        >
                            Simpan
                        </button>
                        <p
                            v-if="profileForm.recentlySuccessful"
                            class="text-sm text-gray-600"
                        >
                            Tersimpan.
                        </p>
                    </div>
                </form>
            </section>

            <!-- Ganti Password -->
            <section class="rounded-lg bg-white p-6 shadow">
                <h2 class="text-lg font-medium text-gray-900">
                    Ganti Password
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Pastikan pakai password yang panjang dan acak.
                </p>

                <form @submit.prevent="updatePassword" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Password Saat Ini</label
                        >
                        <input
                            ref="currentPasswordInput"
                            v-model="passwordForm.current_password"
                            type="password"
                            autocomplete="current-password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        />
                        <p
                            v-if="passwordForm.errors.current_password"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Password Baru</label
                        >
                        <input
                            ref="passwordInput"
                            v-model="passwordForm.password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        />
                        <p
                            v-if="passwordForm.errors.password"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Konfirmasi Password Baru</label
                        >
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        />
                        <p
                            v-if="passwordForm.errors.password_confirmation"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ passwordForm.errors.password_confirmation }}
                        </p>
                    </div>
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                        :disabled="passwordForm.processing"
                    >
                        Simpan Password
                    </button>
                </form>
            </section>

            <!-- Hapus Akun -->
            <section
                class="rounded-lg border border-red-200 bg-white p-6 shadow"
            >
                <h2 class="text-lg font-medium text-red-700">Hapus Akun</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Setelah akun dihapus, semua data terkait akan hilang
                    permanen. Unduh data penting sebelum lanjut.
                </p>

                <button
                    v-if="!showDeleteConfirm"
                    type="button"
                    @click="confirmDelete"
                    class="mt-4 rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                >
                    Hapus Akun
                </button>

                <form
                    v-else
                    @submit.prevent="deleteAccount"
                    class="mt-4 space-y-3"
                >
                    <label class="block text-sm font-medium text-gray-700">
                        Masukkan password untuk konfirmasi
                    </label>
                    <input
                        v-model="deleteForm.password"
                        type="password"
                        class="block w-full rounded-md border-gray-300 shadow-sm"
                        required
                        autofocus
                    />
                    <p
                        v-if="deleteForm.errors.password"
                        class="text-sm text-red-600"
                    >
                        {{ deleteForm.errors.password }}
                    </p>
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            class="rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                            :disabled="deleteForm.processing"
                        >
                            Ya, Hapus Akun Saya
                        </button>
                        <button
                            type="button"
                            @click="showDeleteConfirm = false"
                            class="rounded-md bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200"
                        >
                            Batal
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
