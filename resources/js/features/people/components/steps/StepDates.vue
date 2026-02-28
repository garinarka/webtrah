<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    form: Object,
    errors: Object,
});

const isDeceased = ref(!!props.form.death_date);

const updateField = (field, value) => {
    props.form[field] = value;
};

watch(isDeceased, (val) => {
    if (!val) {
        props.form.death_date      = '';
        props.form.death_accuracy  = 'unknown';
    }
});

const accuracyOptions = [
    { value: 'exact',      label: 'Tanggal Tepat',    hint: 'Contoh: 15 Mei 1940' },
    { value: 'year_month', label: 'Bulan & Tahun',    hint: 'Contoh: Mei 1940' },
    { value: 'year',       label: 'Tahun Saja',       hint: 'Contoh: 1940' },
    { value: 'unknown',    label: 'Tidak Diketahui',  hint: 'Tanggal tidak tersedia' },
];

const showDateInput = (accuracy) => ['exact', 'year_month', 'year'].includes(accuracy);

const inputType = (accuracy) => {
    if (accuracy === 'exact')      return 'date';
    if (accuracy === 'year_month') return 'month';
    if (accuracy === 'year')       return 'number';
    return 'date';
};
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Tanggal Lahir & Wafat</h2>
            <p class="mt-1 text-sm text-gray-500">
                Isi sebisa mungkin. Gunakan akurasi yang sesuai dengan data yang tersedia.
            </p>
        </div>

        <!-- Birth Date Section -->
        <div class="bg-blue-50 rounded-xl p-5 space-y-4 border border-blue-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm">👶</span>
                </div>
                <h3 class="font-medium text-blue-900">Tanggal Lahir</h3>
            </div>

            <!-- Accuracy Selector -->
            <div>
                <label class="block text-xs font-medium text-blue-700 uppercase tracking-wider mb-2">
                    Tingkat Akurasi
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="opt in accuracyOptions"
                        :key="opt.value"
                        type="button"
                        @click="updateField('birth_accuracy', opt.value)"
                        :class="[
                            'text-left p-2.5 rounded-lg border text-xs transition-all duration-150',
                            form.birth_accuracy === opt.value
                                ? 'border-blue-500 bg-white shadow-sm text-blue-800 font-medium'
                                : 'border-blue-200 bg-blue-50/50 text-blue-600 hover:bg-white',
                        ]"
                    >
                        <div class="font-medium">{{ opt.label }}</div>
                        <div class="text-gray-400 mt-0.5">{{ opt.hint }}</div>
                    </button>
                </div>
            </div>

            <!-- Date Input -->
            <div v-if="showDateInput(form.birth_accuracy)">
                <label class="block text-xs font-medium text-blue-700 uppercase tracking-wider mb-1">
                    {{ form.birth_accuracy === 'year' ? 'Tahun Lahir' : 'Tanggal Lahir' }}
                </label>
                <input
                    :type="inputType(form.birth_accuracy)"
                    :value="form.birth_date"
                    @change="updateField('birth_date', $event.target.value)"
                    :min="form.birth_accuracy === 'year' ? '1800' : '1800-01-01'"
                    :max="form.birth_accuracy === 'year' ? new Date().getFullYear() : new Date().toISOString().split('T')[0]"
                    :class="[
                        'block w-full rounded-lg border px-3 py-2 text-gray-900 text-sm shadow-sm',
                        'focus:outline-none focus:ring-2 focus:ring-blue-300 transition-colors',
                        errors.birth_date ? 'border-red-300 bg-red-50' : 'border-blue-200 bg-white focus:border-blue-400',
                    ]"
                />
                <p v-if="errors.birth_date" class="mt-1 text-xs text-red-600">⚠ {{ errors.birth_date }}</p>
            </div>
            <div v-else class="text-xs text-blue-600 italic">
                Tanggal tidak akan diisi karena akurasi "Tidak Diketahui".
            </div>
        </div>

        <!-- Deceased Toggle -->
        <div>
            <label class="flex items-center gap-3 cursor-pointer group">
                <div class="relative">
                    <input type="checkbox" v-model="isDeceased" class="sr-only" />
                    <div :class="[
                        'w-11 h-6 rounded-full transition-colors duration-200',
                        isDeceased ? 'bg-gray-600' : 'bg-gray-200',
                    ]"/>
                    <div :class="[
                        'absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200',
                        isDeceased ? 'translate-x-5' : 'translate-x-0',
                    ]"/>
                </div>
                <span class="text-sm font-medium text-gray-700">Anggota ini telah meninggal dunia</span>
            </label>
        </div>

        <!-- Death Date Section -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="isDeceased" class="bg-gray-50 rounded-xl p-5 space-y-4 border border-gray-200">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm">🕊</span>
                    </div>
                    <h3 class="font-medium text-gray-700">Tanggal Meninggal</h3>
                </div>

                <!-- Accuracy Selector -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-2">
                        Tingkat Akurasi
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="opt in accuracyOptions"
                            :key="opt.value"
                            type="button"
                            @click="updateField('death_accuracy', opt.value)"
                            :class="[
                                'text-left p-2.5 rounded-lg border text-xs transition-all duration-150',
                                form.death_accuracy === opt.value
                                    ? 'border-gray-500 bg-white shadow-sm text-gray-800 font-medium'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50',
                            ]"
                        >
                            <div class="font-medium">{{ opt.label }}</div>
                            <div class="text-gray-400 mt-0.5">{{ opt.hint }}</div>
                        </button>
                    </div>
                </div>

                <!-- Death Date Input -->
                <div v-if="showDateInput(form.death_accuracy)">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">
                        {{ form.death_accuracy === 'year' ? 'Tahun Meninggal' : 'Tanggal Meninggal' }}
                    </label>
                    <input
                        :type="inputType(form.death_accuracy)"
                        :value="form.death_date"
                        @change="updateField('death_date', $event.target.value)"
                        :min="form.birth_date || '1800-01-01'"
                        :max="new Date().toISOString().split('T')[0]"
                        :class="[
                            'block w-full rounded-lg border px-3 py-2 text-gray-900 text-sm shadow-sm',
                            'focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors',
                            errors.death_date ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white focus:border-gray-400',
                        ]"
                    />
                    <p v-if="errors.death_date" class="mt-1 text-xs text-red-600">⚠ {{ errors.death_date }}</p>
                </div>
            </div>
        </Transition>
    </div>
</template>
