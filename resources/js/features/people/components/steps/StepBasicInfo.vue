<script setup>
import { watch } from 'vue';
import { useDuplicateDetection } from '../../composables/useDuplicateDetection.js';

const props = defineProps({
    form: Object,
    errors: Object,
    familyUnitId: String,
});

const emit = defineEmits(['update:form']);

const { hasWarning, isChecking, riskConfig, allMatches, scheduleCheck, dismiss } = useDuplicateDetection();

const updateField = (field, value) => {
    props.form[field] = value;
};

watch(() => props.form.display_name, (name) => {
    scheduleCheck({
        display_name:   name,
        family_unit_id: props.familyUnitId,
    });
});

const genders = [
    { value: 'male',    label: 'Laki-laki',       icon: '♂', color: 'blue'  },
    { value: 'female',  label: 'Perempuan',        icon: '♀', color: 'pink'  },
    { value: 'unknown', label: 'Tidak Diketahui',  icon: '?', color: 'gray'  },
];
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Informasi Dasar</h2>
            <p class="mt-1 text-sm text-gray-500">Nama dan jenis kelamin anggota keluarga.</p>
        </div>

        <!-- Name Field -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Nama Tampilan <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input
                    :value="form.display_name"
                    @input="updateField('display_name', $event.target.value)"
                    type="text"
                    placeholder="Contoh: Surya Wijaya"
                    autocomplete="off"
                    :class="[
                        'block w-full rounded-lg border px-4 py-2.5 text-gray-900 shadow-sm',
                        'focus:outline-none focus:ring-2 transition-colors',
                        errors.display_name
                            ? 'border-red-300 focus:border-red-500 focus:ring-red-200 bg-red-50'
                            : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-200',
                    ]"
                />
                <!-- Checking spinner -->
                <div v-if="isChecking" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            </div>

            <p v-if="errors.display_name" class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                <span>⚠</span> {{ errors.display_name }}
            </p>
            <p v-else class="mt-1.5 text-xs text-gray-400">
                Gunakan nama lengkap atau nama panggilan yang dikenal.
            </p>
        </div>

        <!-- Duplicate Warning -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="hasWarning"
                :class="['rounded-lg border p-4', riskConfig.bg]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-2">
                        <span class="text-lg leading-none">{{ riskConfig.icon }}</span>
                        <div>
                            <p :class="['text-sm font-semibold', riskConfig.text]">
                                {{ riskConfig.label }}
                            </p>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Ditemukan {{ allMatches.length }} anggota dengan nama serupa:
                            </p>
                            <ul class="mt-2 space-y-1">
                                <li
                                    v-for="match in allMatches"
                                    :key="match.id"
                                    class="text-xs text-gray-700 flex items-center gap-2"
                                >
                                    <span :class="[
                                        'w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0',
                                        match.gender === 'male' ? 'bg-blue-500' : match.gender === 'female' ? 'bg-pink-500' : 'bg-gray-400'
                                    ]">
                                        {{ match.display_name?.charAt(0) }}
                                    </span>
                                    <span class="font-medium">{{ match.display_name }}</span>
                                    <span class="text-gray-400">
                                        {{ match.birth_date ? new Date(match.birth_date).getFullYear() : '?' }}
                                    </span>
                                    <a :href="`/people/${match.id}`" target="_blank"
                                        class="text-indigo-600 hover:underline ml-auto">
                                        Lihat →
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <button @click="dismiss" class="text-gray-400 hover:text-gray-600 flex-shrink-0 text-lg leading-none">
                        ×
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Gender Field -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Jenis Kelamin <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-3 gap-3">
                <button
                    v-for="g in genders"
                    :key="g.value"
                    type="button"
                    @click="updateField('gender', g.value)"
                    :class="[
                        'flex flex-col items-center justify-center rounded-xl border-2 p-4 transition-all duration-150 cursor-pointer',
                        form.gender === g.value
                            ? g.value === 'male'   ? 'border-blue-500 bg-blue-50 shadow-sm'
                            : g.value === 'female' ? 'border-pink-500 bg-pink-50 shadow-sm'
                            :                        'border-gray-500 bg-gray-50 shadow-sm'
                            : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50',
                        errors.gender ? 'ring-1 ring-red-300' : '',
                    ]"
                >
                    <span class="text-2xl mb-1">{{ g.icon }}</span>
                    <span :class="[
                        'text-xs font-medium',
                        form.gender === g.value
                            ? g.value === 'male'   ? 'text-blue-700'
                            : g.value === 'female' ? 'text-pink-700'
                            :                        'text-gray-700'
                            : 'text-gray-600'
                    ]">
                        {{ g.label }}
                    </span>
                </button>
            </div>
            <p v-if="errors.gender" class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                <span>⚠</span> {{ errors.gender }}
            </p>
        </div>
    </div>
</template>
