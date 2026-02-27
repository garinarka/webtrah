<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import RadialView from './RadialView.vue';
import TimelineView from './TimelineView.vue';

const currentMode = ref('radial'); // 'radial' | 'timeline'

const modes = [
    { id: 'radial', name: 'Radial', icon: 'Circle' },
    { id: 'timeline', name: 'Timeline', name: 'Timeline', icon: 'List' },
];
</script>

<template>

    <Head title="Pohon Keluarga" />
    <AppLayout>
        <div class="h-[600px] flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Pohon Keluarga</h1>

                <div class="flex bg-gray-100 rounded-lg p-1">
                    <button v-for="mode in modes" :key="mode.id" @click="currentMode = mode.id"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        :class="currentMode === mode.id ? 'bg-white text-indigo-600 shadow' : 'text-gray-500 hover:text-gray-700'">
                        {{ mode.name }}
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-white rounded-lg shadow overflow-hidden relative min-h-[500px]">
                <KeepAlive>
                    <component :is="currentMode === 'radial' ? RadialView : TimelineView" />
                </KeepAlive>
            </div>
        </div>
    </AppLayout>
</template>
