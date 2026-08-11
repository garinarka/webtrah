<script setup>
import { ref, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import QRCode from 'qrcode'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    secret: { type: String, required: true },
    qrCodeUrl: { type: String, required: true },
    status: { type: String, default: null },
})

const qrCanvas = ref(null)

onMounted(() => {
    QRCode.toCanvas(qrCanvas.value, props.qrCodeUrl, { width: 220 })
})

const form = useForm({ code: '' })

const submit = () => {
    form.post(route('two-factor.confirm'))
}
</script>

<template>
    <Head title="Setup 2FA" />
    <AppLayout>
        <div class="mx-auto max-w-md p-4">
            <div class="rounded-lg bg-white p-6 shadow">
                <div
                    v-if="status"
                    class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                >
                    {{ status }}
                </div>

                <h1 class="text-xl font-bold text-gray-900">
                    Aktifkan Verifikasi 2 Langkah
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Scan QR code ini pakai aplikasi authenticator (Google
                    Authenticator, Authy, atau 1Password).
                </p>

                <div class="mt-4 flex justify-center">
                    <canvas ref="qrCanvas"></canvas>
                </div>

                <p class="mt-4 text-center text-xs text-gray-500">
                    Tidak bisa scan? Masukkan kode ini manual di aplikasi
                    authenticator:
                    <span
                        class="mt-1 block break-all font-mono font-medium text-gray-800"
                        >{{ secret }}</span
                    >
                </p>

                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Masukkan 6 digit kode dari aplikasi authenticator
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
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
                        Konfirmasi & Aktifkan
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
