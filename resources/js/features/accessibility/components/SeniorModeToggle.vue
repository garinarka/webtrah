<script setup>
import { usePreferencesStore } from '@/stores/preferences';

const preferences = usePreferencesStore();

const fontSizes = [
    { value: 'normal', label: 'Normal', desc: '18px' },
    { value: 'large', label: 'Besar', desc: '20px' },
    { value: 'xlarge', label: 'Sangat Besar', desc: '22px' }
];
</script>

<template>
    <div class="bg-white rounded-lg p-4 space-y-4">
        <h3 class="font-semibold text-gray-900 text-lg">Pengaturan Tampilan</h3>

        <!-- senior mode toggle -->
        <div class="flex items-center justify-between py-3 border-b">
            <div>
                <div class="font-medium text-gray-900">Mode Lansia</div>
                <div class="text-sm text-gray-500">Font lebih besar & mudah dibaca</div>
            </div>
            <button @click="preferences.seniorMode = !preferences.seniorMode"
                class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
                :class="preferences.seniorMode ? 'bg-indigo-600' : 'bg-gray-200'">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform shadow"
                    :class="preferences.seniorMode ? 'translate-x-6' : 'translate-x-1'"></span>
            </button>
        </div>

        <!-- font size (only when senior mode active) -->
        <div v-if="preferences.seniorMode" class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ukuran Font Dasar
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <button v-for="size in fontSizes" :key="size.value" @click="preferences.fontSize = size.value"
                        class="flex flex-col items-center py-3 px-2 rounded-lg border-2 transition-all" :class="preferences.fontSize === size.value
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'">
                        <span class="font-medium">{{ size.label }}</span>
                        <span class="text-xs mt-1 opacity-75">{{ size.desc }}</span>
                    </button>
                </div>
            </div>

            <!-- high contrast -->
            <label class="flex items-center justify-between py-2 cursor-pointer">
                <span class="text-gray-700">Kontras Tinggi</span>
                <input type="checkbox" v-model="preferences.highContrast"
                    class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
            </label>

            <!-- reduced motion -->
            <label class="flex items-center justify-between py-2 cursor-pointer">
                <span class="text-gray-700">Kurangi Animasi</span>
                <input type="checkbox" v-model="preferences.reducedMotion"
                    class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
            </label>
        </div>

        <!-- preview text -->
        <div v-if="preferences.seniorMode" class="mt-4 p-3 bg-gray-50 rounded-lg" :class="[
            preferences.fontSize === 'normal' ? 'text-base' :
                preferences.fontSize === 'large' ? 'text-lg' : 'text-xl'
        ]">
            <p class="text-gray-600 text-sm mb-1">Preview:</p>
            <p>Ini adalah contoh teks dengan ukuran yang dipilih.</p>
        </div>
    </div>
</template>
