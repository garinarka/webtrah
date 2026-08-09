<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

defineProps({
    recoveryCodes: { type: Array, required: true },
})

const print = () => window.print()
</script>

<template>
    <Head title="Recovery Codes 2FA" />
    <AppLayout>
        <div class="mx-auto max-w-md p-4">
            <div class="rounded-lg bg-white p-6 shadow">
                <h1 class="text-xl font-bold text-gray-900">
                    2FA Aktif! Simpan Recovery Codes Ini
                </h1>
                <p class="mt-2 text-sm font-medium text-red-600">
                    Ini SATU-SATUNYA kesempatan kamu lihat kode ini. Simpan di
                    tempat aman (password manager, kertas dikunci) — dipakai
                    kalau HP/authenticator hilang.
                </p>

                <div
                    class="mt-4 space-y-1 rounded-md bg-gray-50 p-4 font-mono text-sm"
                >
                    <div v-for="code in recoveryCodes" :key="code">
                        {{ code }}
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button
                        @click="print"
                        class="rounded-md bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200"
                    >
                        Cetak / Simpan PDF
                    </button>
                    <Link
                        :href="route('profile.edit')"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    >
                        Sudah Disimpan, Lanjut
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
