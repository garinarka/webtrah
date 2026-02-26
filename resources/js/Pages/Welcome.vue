<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>

    <Head title="Welcome" />
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900">Family Tree App</h1>
            <p class="mt-4 text-gray-600">Sistem genealogi keluarga</p>
            <div class="mt-8 space-x-4">
                <Link v-if="canLogin" href="/login"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Masuk</Link>
                <Link v-if="canRegister" href="/register"
                    class="px-4 py-2 bg-white text-gray-700 border rounded-md hover:bg-gray-50">Daftar</Link>
            </div>
        </div>
    </div>
</template>
