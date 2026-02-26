<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>

    <Head title="Log in" />
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow">
            <h2 class="text-2xl font-bold text-center text-gray-900">Masuk</h2>
            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input v-model="form.email" type="email"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input v-model="form.password" type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                </div>
                <div class="flex items-center">
                    <input v-model="form.remember" type="checkbox" class="rounded border-gray-300" />
                    <label class="ml-2 text-sm text-gray-600">Ingat saya</label>
                </div>
                <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    :disabled="form.processing">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</template>
