<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Reset Password" />
    <div class="flex min-h-screen items-center justify-center bg-gray-50">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow">
            <h2 class="text-center text-2xl font-bold text-gray-900">
                Reset Password
            </h2>
            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Email</label
                    >
                    <input
                        v-model="form.email"
                        type="email"
                        autofocus
                        autocomplete="username"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        required
                    />
                    <p
                        v-if="form.errors.email"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Password Baru</label
                    >
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        required
                    />
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Konfirmasi Password</label
                    >
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        required
                    />
                    <p
                        v-if="form.errors.password_confirmation"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>
                <button
                    type="submit"
                    class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    :disabled="form.processing"
                >
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</template>
