<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    status: {
        type: String,
    },
})

const form = useForm({})

const submit = () => {
    form.post(route('verification.send'))
}

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
)
</script>

<template>
    <Head title="Verifikasi Email" />
    <div class="flex min-h-screen items-center justify-center bg-gray-50">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow">
            <h2 class="text-center text-2xl font-bold text-gray-900">
                Verifikasi Email
            </h2>
            <p class="mt-4 text-sm text-gray-600">
                Terima kasih sudah daftar! Sebelum mulai, tolong verifikasi
                email kamu dengan klik link yang sudah kami kirim. Kalau belum
                dapat email-nya, kami bisa kirim ulang.
            </p>

            <div
                v-if="verificationLinkSent"
                class="mt-4 text-sm font-medium text-green-600"
            >
                Link verifikasi baru sudah dikirim ke email yang kamu daftarkan.
            </div>

            <form
                @submit.prevent="submit"
                class="mt-6 flex items-center justify-between"
            >
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    :disabled="form.processing"
                >
                    Kirim Ulang Email Verifikasi
                </button>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-gray-600 underline hover:text-gray-900"
                >
                    Keluar
                </Link>
            </form>
        </div>
    </div>
</template>
