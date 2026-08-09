<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const form = useForm({ code: '' })

const submit = () => {
    form.post(route('two-factor.verify'))
}
</script>

<template>
    <Head title="Verifikasi 2FA" />
    <div class="flex min-h-screen items-center justify-center bg-gray-50">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow">
            <h2 class="text-center text-2xl font-bold text-gray-900">
                Verifikasi 2 Langkah
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Masukkan kode dari aplikasi authenticator kamu, atau salah satu
                recovery code.
            </p>

            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <input
                        v-model="form.code"
                        type="text"
                        inputmode="numeric"
                        autofocus
                        class="mt-1 block w-full rounded-md border-gray-300 text-center text-lg tracking-widest shadow-sm"
                        placeholder="000000"
                    />
                    <p
                        v-if="form.errors.code"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.code }}
                    </p>
                </div>
                <button
                    type="submit"
                    class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    :disabled="form.processing"
                >
                    Verifikasi
                </button>
            </form>
        </div>
    </div>
</template>
