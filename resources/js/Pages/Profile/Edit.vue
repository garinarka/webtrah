<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
})

const user = usePage().props.auth.user

// ── Foto profil: pilih file -> crop -> preview hasil crop -> simpan ──────
const avatarInput = ref(null)
const cropperRef = ref(null)
const rawImageSrc = ref(null) // gambar asli (belum di-crop), buat modal
const showCropModal = ref(false)
const croppedPreview = ref(null) // hasil crop, ditampilkan sebelum disimpan
const croppedBlob = ref(null) // hasil crop dalam bentuk Blob, siap upload
const avatarForm = useForm({ avatar: null })

const pickAvatar = () => avatarInput.value?.click()

const onFileSelected = (e) => {
    const file = e.target.files?.[0]
    if (!file) return

    const reader = new FileReader()
    reader.onload = () => {
        rawImageSrc.value = reader.result
        showCropModal.value = true
    }
    reader.readAsDataURL(file)

    // reset input biar bisa pilih file yang sama lagi kalau mau ulang crop
    e.target.value = ''
}

const confirmCrop = () => {
    const { canvas } = cropperRef.value.getResult()
    if (!canvas) return

    canvas.toBlob(
        (blob) => {
            croppedBlob.value = blob
            croppedPreview.value = URL.createObjectURL(blob)
            showCropModal.value = false
            rawImageSrc.value = null
        },
        'image/jpeg',
        0.9,
    )
}

const cancelCrop = () => {
    showCropModal.value = false
    rawImageSrc.value = null
}

const saveAvatar = () => {
    if (!croppedBlob.value) return

    avatarForm.avatar = new File([croppedBlob.value], 'avatar.jpg', {
        type: 'image/jpeg',
    })

    avatarForm.post(route('profile.avatar.update'), {
        preserveScroll: true,
        onSuccess: () => {
            croppedPreview.value = null
            croppedBlob.value = null
        },
    })
}

const cancelSave = () => {
    croppedPreview.value = null
    croppedBlob.value = null
    avatarForm.reset()
}

const deleteAvatarForm = useForm({})
const removeAvatar = () => {
    deleteAvatarForm.delete(route('profile.avatar.delete'), {
        preserveScroll: true,
    })
}

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

// 2FA disable
const showDisable2fa = ref(false)
const disable2faForm = useForm({ password: '' })
const disable2fa = () => {
    disable2faForm.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            showDisable2fa.value = false
            disable2faForm.reset()
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

            <!-- Foto Profil -->
            <section class="rounded-lg bg-white p-6 shadow">
                <h2 class="text-lg font-medium text-gray-900">Foto Profil</h2>

                <div class="mt-4 flex items-center gap-5">
                    <div class="relative">
                        <img
                            v-if="croppedPreview || user.avatar_url"
                            :src="croppedPreview || user.avatar_url"
                            alt="Foto profil"
                            class="h-20 w-20 rounded-full object-cover ring-2 ring-gray-100"
                        />
                        <div
                            v-else
                            class="flex h-20 w-20 items-center justify-center rounded-full bg-indigo-100 text-2xl font-semibold text-indigo-700"
                        >
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>

                        <div
                            v-if="avatarForm.processing"
                            class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40"
                        >
                            <span class="text-xs text-white">...</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <input
                            ref="avatarInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            @change="onFileSelected"
                        />

                        <!-- Belum ada crop pending: tombol pilih foto -->
                        <template v-if="!croppedPreview">
                            <button
                                type="button"
                                @click="pickAvatar"
                                class="block rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700"
                            >
                                {{
                                    user.avatar_url
                                        ? 'Ganti Foto'
                                        : 'Unggah Foto'
                                }}
                            </button>
                            <button
                                v-if="user.avatar_url"
                                type="button"
                                @click="removeAvatar"
                                class="block text-sm text-red-600 hover:text-red-700"
                            >
                                Hapus Foto
                            </button>
                        </template>

                        <!-- Sudah di-crop, belum disimpan: tombol Simpan/Batal eksplisit -->
                        <template v-else>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    @click="saveAvatar"
                                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700"
                                    :disabled="avatarForm.processing"
                                >
                                    Simpan Foto
                                </button>
                                <button
                                    type="button"
                                    @click="cancelSave"
                                    class="rounded-md bg-gray-100 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-200"
                                    :disabled="avatarForm.processing"
                                >
                                    Batal
                                </button>
                            </div>
                            <p class="text-xs text-amber-600">
                                Belum tersimpan — klik "Simpan Foto" dulu.
                            </p>
                        </template>

                        <p class="text-xs text-gray-500">
                            JPG, PNG, atau WEBP. Maksimal 2MB.
                        </p>
                        <p
                            v-if="avatarForm.errors.avatar"
                            class="text-xs text-red-600"
                        >
                            {{ avatarForm.errors.avatar }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Modal Crop Foto -->
            <div
                v-if="showCropModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
                    <div class="border-b border-gray-100 p-4">
                        <h3 class="font-medium text-gray-900">
                            Sesuaikan Foto
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Geser & perbesar/perkecil area lingkaran.
                        </p>
                    </div>

                    <div class="p-4">
                        <Cropper
                            ref="cropperRef"
                            :src="rawImageSrc"
                            :stencil-props="{ aspectRatio: 1 }"
                            class="h-72 overflow-hidden rounded-md bg-gray-50"
                        />
                    </div>

                    <div
                        class="flex justify-end gap-2 border-t border-gray-100 p-4"
                    >
                        <button
                            type="button"
                            @click="cancelCrop"
                            class="rounded-md bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="confirmCrop"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                        >
                            Pakai Foto Ini
                        </button>
                    </div>
                </div>
            </div>

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

            <!-- Verifikasi 2 Langkah (2FA) -->
            <section class="rounded-lg bg-white p-6 shadow">
                <h2 class="text-lg font-medium text-gray-900">
                    Verifikasi 2 Langkah (2FA)
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Lapisan keamanan tambahan pakai aplikasi authenticator di HP
                    kamu.
                </p>

                <div v-if="user.two_factor_enabled" class="mt-4">
                    <span
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-green-700"
                    >
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                        Aktif
                    </span>

                    <form v-if="!showDisable2fa" class="mt-3">
                        <button
                            type="button"
                            @click="showDisable2fa = true"
                            class="rounded-md bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200"
                        >
                            Nonaktifkan 2FA
                        </button>
                    </form>
                    <form
                        v-else
                        @submit.prevent="disable2fa"
                        class="mt-3 space-y-3"
                    >
                        <label class="block text-sm font-medium text-gray-700">
                            Masukkan password untuk konfirmasi
                        </label>
                        <input
                            v-model="disable2faForm.password"
                            type="password"
                            class="block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        />
                        <p
                            v-if="disable2faForm.errors.password"
                            class="text-sm text-red-600"
                        >
                            {{ disable2faForm.errors.password }}
                        </p>
                        <div class="flex gap-3">
                            <button
                                type="submit"
                                class="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
                                :disabled="disable2faForm.processing"
                            >
                                Ya, Nonaktifkan
                            </button>
                            <button
                                type="button"
                                @click="showDisable2fa = false"
                                class="rounded-md bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200"
                            >
                                Batal
                            </button>
                        </div>
                    </form>
                </div>

                <div v-else class="mt-4">
                    <span
                        v-if="user.role === 'admin'"
                        class="mb-3 block text-sm font-medium text-red-600"
                    >
                        Wajib untuk akun admin — belum aktif.
                    </span>
                    <Link
                        :href="route('two-factor.setup')"
                        class="inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                    >
                        Aktifkan 2FA
                    </Link>
                </div>
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
