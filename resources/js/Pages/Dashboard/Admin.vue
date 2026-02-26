<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Dashboard Admin" />
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard Admin</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola trah keluarga dan persetujuan</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-md bg-indigo-500 flex items-center justify-center text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500">Total Anggota</dt>
                            <dd class="text-lg font-medium text-gray-900">127</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-md bg-amber-500 flex items-center justify-center text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500">Menunggu Approval</dt>
                            <dd class="text-lg font-medium text-gray-900">3</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg p-5">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-md bg-green-500 flex items-center justify-center text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500">Perubahan Bulan Ini</dt>
                            <dd class="text-lg font-medium text-gray-900">12</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900">Aksi Cepat</h2>
            <div class="mt-4 flex space-x-4">
                <a href="/approvals"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">Lihat
                    Persetujuan</a>
                <a href="/admin/moderators"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">Kelola
                    Moderator</a>
            </div>
        </div>
    </div>
</template>
