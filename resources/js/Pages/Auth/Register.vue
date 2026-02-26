<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>

    <Head title="Register" />
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow">
            <h2 class="text-2xl font-bold text-center text-gray-900">Daftar</h2>
            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama</label>
                    <input v-model="form.name" type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required autofocus />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input v-model="form.email" type="email"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input v-model="form.password" type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required
                        autocomplete="new-password" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                    <input v-model="form.password_confirmation" type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required
                        autocomplete="new-password" />
                </div>
                <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    :disabled="form.processing">
                    Daftar
                </button>
            </form>
        </div>
    </div>
</template>
