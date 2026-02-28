<script setup>
const props = defineProps({
    form:        Object,
    errors:      Object,
    familyUnits: Array,
});

const updateField = (field, value) => {
    props.form[field] = value;
};

const selectedUnit = () =>
    props.familyUnits?.find(u => u.id === props.form.family_unit_id) ?? null;
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Unit Keluarga</h2>
            <p class="mt-1 text-sm text-gray-500">
                Pilih kelompok keluarga (trah) tempat anggota ini terdaftar.
            </p>
        </div>

        <!-- Family Unit Cards -->
        <div class="space-y-2">
            <button
                v-for="unit in familyUnits"
                :key="unit.id"
                type="button"
                @click="updateField('family_unit_id', unit.id)"
                :class="[
                    'w-full text-left flex items-center gap-4 p-4 rounded-xl border-2 transition-all duration-150',
                    form.family_unit_id === unit.id
                        ? 'border-indigo-500 bg-indigo-50 shadow-sm'
                        : 'border-gray-200 bg-white hover:border-indigo-200 hover:bg-gray-50',
                ]"
            >
                <!-- Icon -->
                <div :class="[
                    'w-12 h-12 rounded-full flex items-center justify-center text-xl flex-shrink-0 transition-colors',
                    form.family_unit_id === unit.id ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-500',
                ]">
                    🏠
                </div>

                <!-- Label -->
                <div class="flex-1">
                    <p :class="[
                        'font-semibold',
                        form.family_unit_id === unit.id ? 'text-indigo-800' : 'text-gray-900',
                    ]">
                        {{ unit.name }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">ID: {{ unit.id }}</p>
                </div>

                <!-- Check indicator -->
                <div v-if="form.family_unit_id === unit.id"
                    class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </button>

            <!-- Empty state -->
            <div v-if="!familyUnits?.length"
                class="text-center p-8 rounded-xl bg-gray-50 border-2 border-dashed border-gray-200">
                <div class="text-4xl mb-3">🏡</div>
                <p class="text-sm text-gray-500">Belum ada unit keluarga tersedia.</p>
                <p class="text-xs text-gray-400 mt-1">Hubungi administrator untuk menambahkan.</p>
            </div>
        </div>

        <p v-if="errors.family_unit_id" class="text-sm text-red-600 flex items-center gap-1">
            <span>⚠</span> {{ errors.family_unit_id }}
        </p>

        <!-- Selected summary -->
        <div v-if="selectedUnit()" class="rounded-lg bg-indigo-50 border border-indigo-200 px-4 py-3">
            <p class="text-sm text-indigo-700">
                <span class="font-medium">Dipilih:</span> {{ selectedUnit().name }}
            </p>
        </div>
    </div>
</template>
