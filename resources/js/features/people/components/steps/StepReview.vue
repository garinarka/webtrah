<script setup>
const props = defineProps({
    form: Object,
    errors: Object,   // allErrors dari wizard (termasuk client-side errors)
    familyUnits: Array,
    genderLabel: String,
    accuracyLabel: Function,
    isEditMode: { type: Boolean, default: false },
    isAdmin: { type: Boolean, default: false },
});

const familyUnitName = () =>
    props.familyUnits?.find(u => u.id === props.form.family_unit_id)?.name ?? '-';

/**
 * Format tanggal untuk preview — parse komponen string langsung (hindari timezone shift).
 * new Date("2025-01-01") = UTC midnight → UTC+7 tampil sebagai 31 Des 2024.
 */
const formatDate = (val, accuracy) => {
    if (!val || accuracy === 'unknown') return 'Tidak diketahui';
    try {
        const parts = String(val).split('-').map(Number);
        const year = parts[0];
        const month = parts[1] ?? 1;
        const day = parts[2] ?? 1;
        if (accuracy === 'year') return String(year);
        // buat date dari komponen lokal (bukan UTC string)
        const d = new Date(year, month - 1, day);
        if (accuracy === 'year_month') return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long' });
        return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch {
        return val;
    }
};

const reviewItems = [
    {
        group: 'Identitas', icon: '👤',
        rows: [
            { label: 'Nama', value: () => props.form.display_name || '-' },
            { label: 'Jenis Kelamin', value: () => props.genderLabel || '-' },
        ],
    },
    {
        group: 'Tanggal', icon: '📅',
        rows: [
            { label: 'Lahir', value: () => formatDate(props.form.birth_date, props.form.birth_accuracy) },
            { label: 'Akurasi Lahir', value: () => props.accuracyLabel(props.form.birth_accuracy) },
            { label: 'Meninggal', value: () => formatDate(props.form.death_date, props.form.death_accuracy) },
            { label: 'Akurasi Wafat', value: () => props.accuracyLabel(props.form.death_accuracy) },
        ],
    },
    {
        group: 'Keluarga', icon: '🏠',
        rows: [
            { label: 'Unit Keluarga', value: familyUnitName },
        ],
    },
];
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Tinjau & Konfirmasi</h2>
            <p class="mt-1 text-sm text-gray-500">Periksa kembali data sebelum diajukan.</p>
        </div>

        <!-- preview card -->
        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 py-5 flex items-center gap-4">
                <div :class="[
                    'w-14 h-14 rounded-full flex items-center justify-center text-2xl font-bold text-white flex-shrink-0',
                    form.gender === 'male' ? 'bg-blue-400' : form.gender === 'female' ? 'bg-pink-400' : 'bg-indigo-300',
                ]">
                    {{ form.display_name?.charAt(0)?.toUpperCase() || '?' }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">{{ form.display_name || 'Belum diisi' }}</h3>
                    <p class="text-indigo-200 text-sm">{{ genderLabel }}</p>
                </div>
            </div>

            <div class="divide-y divide-gray-100">
                <div v-for="group in reviewItems" :key="group.group" class="px-6 py-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-base">{{ group.icon }}</span>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ group.group }}</h4>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-8 gap-y-2">
                        <div v-for="row in group.rows" :key="row.label" class="flex flex-col">
                            <dt class="text-xs text-gray-400">{{ row.label }}</dt>
                            <dd class="text-sm font-medium text-gray-900 mt-0.5">{{ row.value() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- info box: hanya untuk non-admin -->
        <div v-if="!isAdmin && !isEditMode" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <div class="flex gap-3">
                <span class="text-amber-500 text-lg flex-shrink-0">ℹ</span>
                <div>
                    <p class="text-sm font-medium text-amber-800">Perlu Persetujuan</p>
                    <p class="text-xs text-amber-700 mt-1">
                        Data akan masuk antrian persetujuan sebelum ditampilkan.
                    </p>
                </div>
            </div>
        </div>

        <div v-if="!isAdmin && isEditMode" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="flex gap-3">
                <span class="text-blue-500 text-lg flex-shrink-0">ℹ</span>
                <div>
                    <p class="text-sm font-medium text-blue-800">Perubahan Memerlukan Persetujuan</p>
                    <p class="text-xs text-blue-700 mt-1">
                        Perubahan akan dikonfirmasi oleh admin sebelum diterapkan.
                    </p>
                </div>
            </div>
        </div>

        <!-- edit reason: hanya untuk non-admin saat edit mode -->
        <div v-if="isEditMode && !isAdmin">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Alasan Perubahan <span class="text-red-500">*</span>
            </label>
            <textarea :value="form.edit_reason" @input="form.edit_reason = $event.target.value" rows="3"
                placeholder="Jelaskan mengapa data ini perlu diubah (minimal 5 karakter)..." :class="[
                    'block w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm',
                    'focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400',
                    errors?.edit_reason ? 'border-red-300 bg-red-50' : 'border-gray-300',
                ]" />
            <!-- gunakan errors prop (allErrors) bukan form.errors (server only) -->
            <p v-if="errors?.edit_reason" class="mt-1 text-xs text-red-600">
                ⚠ {{ errors.edit_reason }}
            </p>
        </div>
    </div>
</template>
