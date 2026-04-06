<script setup>
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    form: Object,
    errors: Object,
    familyUnits: Array,
    // Semua orang aktif, difilter di computed berdasarkan unit yang dipilih
    allPeople: { type: Array, default: () => [] },
})

const page = usePage()
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')

// ── FAMILY UNIT SELECTION ─────────────────────────────────────────────────

const selectedUnit = computed(
    () =>
        props.familyUnits?.find((u) => u.id === props.form.family_unit_id) ??
        null,
)

const selectUnit = (id) => {
    props.form.family_unit_id = id
    // Reset relasi saat ganti unit agar tidak stale
    props.form.initial_relations = []
}

// ── INLINE CREATE UNIT KELUARGA (admin only) ──────────────────────────────

const showInlineCreate = ref(false)
const newUnitName = ref('')
const isCreating = ref(false)
const createError = ref('')

const openInlineCreate = () => {
    newUnitName.value = ''
    createError.value = ''
    showInlineCreate.value = true
}

const cancelInlineCreate = () => {
    showInlineCreate.value = false
    newUnitName.value = ''
    createError.value = ''
}

const submitInlineCreate = async () => {
    if (!newUnitName.value.trim()) {
        createError.value = 'Nama unit keluarga wajib diisi.'
        return
    }
    if (newUnitName.value.trim().length < 2) {
        createError.value = 'Nama minimal 2 karakter.'
        return
    }

    isCreating.value = true
    createError.value = ''

    try {
        const res = await window.axios.post('/api/family-units/inline', {
            name: newUnitName.value.trim(),
        })

        const created = res.data // { id, name }

        // Inject ke list dan langsung pilih
        props.familyUnits.push(created)
        selectUnit(created.id)

        cancelInlineCreate()
    } catch (err) {
        createError.value =
            err.response?.data?.message ?? 'Gagal membuat unit keluarga.'
    } finally {
        isCreating.value = false
    }
}

// ── RELASI SECTION ────────────────────────────────────────────────────────

// Orang dari unit keluarga yang dipilih (kecuali diri sendiri)
const peopleInUnit = computed(() => {
    if (!props.form.family_unit_id) return []
    return props.allPeople.filter(
        (p) => p.family_unit_id === props.form.family_unit_id,
    )
})

const relationTypes = [
    { value: 'parent', label: 'Orang Tua Kandung' },
    { value: 'step_parent', label: 'Orang Tua Tiri' },
    { value: 'adopted_parent', label: 'Orang Tua Angkat' },
    { value: 'child', label: 'Anak Kandung' },
    { value: 'step_child', label: 'Anak Tiri' },
    { value: 'adopted_child', label: 'Anak Angkat' },
    { value: 'spouse', label: 'Pasangan / Suami-Istri' },
]

// Form tambah relasi baru
const newRelation = ref({ related_id: '', type: 'parent', is_biological: true })
const relationError = ref('')

// Ensure form.initial_relations exists
if (!props.form.initial_relations) {
    props.form.initial_relations = []
}

const addRelation = () => {
    relationError.value = ''
    if (!newRelation.value.related_id) {
        relationError.value = 'Pilih anggota terlebih dahulu.'
        return
    }

    const alreadyAdded = props.form.initial_relations.some(
        (r) =>
            r.related_id === newRelation.value.related_id &&
            r.type === newRelation.value.type,
    )
    if (alreadyAdded) {
        relationError.value = 'Relasi ini sudah ditambahkan.'
        return
    }

    const person = peopleInUnit.value.find(
        (p) => p.id === newRelation.value.related_id,
    )
    props.form.initial_relations.push({
        related_id: newRelation.value.related_id,
        type: newRelation.value.type,
        is_biological: newRelation.value.is_biological,
        _label: person?.display_name ?? '—',
        _typeLabel:
            relationTypes.find((t) => t.value === newRelation.value.type)
                ?.label ?? newRelation.value.type,
    })

    // Reset form relasi
    newRelation.value = { related_id: '', type: 'parent', is_biological: true }
}

const removeRelation = (index) => {
    props.form.initial_relations.splice(index, 1)
}

const genderIcon = (gender) =>
    ({ male: '👦', female: '👧', unknown: '👤' })[gender] ?? '👤'
</script>

<template>
    <div class="space-y-8">
        <!-- Section header -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Unit Keluarga</h2>
            <p class="mt-1 text-sm text-gray-500">
                Pilih kelompok keluarga tempat anggota ini terdaftar. Setelah
                memilih, kamu bisa langsung mengatur relasi dengan anggota lain
                di unit tersebut.
            </p>
        </div>

        <!-- ── PILIH UNIT KELUARGA ── -->
        <div class="space-y-3">
            <!-- Card list -->
            <div class="space-y-2">
                <button
                    v-for="unit in familyUnits"
                    :key="unit.id"
                    type="button"
                    @click="selectUnit(unit.id)"
                    :class="[
                        'flex w-full items-center gap-4 rounded-xl border-2 p-4 text-left transition-all duration-150',
                        form.family_unit_id === unit.id
                            ? 'border-indigo-500 bg-indigo-50 shadow-sm'
                            : 'border-gray-200 bg-white hover:border-indigo-200 hover:bg-gray-50',
                    ]"
                >
                    <div
                        :class="[
                            'flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full text-xl transition-colors',
                            form.family_unit_id === unit.id
                                ? 'bg-indigo-500 text-white'
                                : 'bg-gray-100 text-gray-500',
                        ]"
                    >
                        🏠
                    </div>
                    <div class="flex-1">
                        <p
                            :class="[
                                'font-semibold',
                                form.family_unit_id === unit.id
                                    ? 'text-indigo-800'
                                    : 'text-gray-900',
                            ]"
                        >
                            {{ unit.name }}
                        </p>
                    </div>
                    <div
                        v-if="form.family_unit_id === unit.id"
                        class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-500"
                    >
                        <svg
                            class="h-3.5 w-3.5 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="3"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                </button>
            </div>

            <!-- Empty state (non-admin) -->
            <div
                v-if="!familyUnits?.length && !isAdmin"
                class="rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center"
            >
                <div class="mb-3 text-4xl">🏡</div>
                <p class="text-sm text-gray-500">
                    Belum ada unit keluarga tersedia.
                </p>
                <p class="mt-1 text-xs text-gray-400">
                    Hubungi administrator untuk menambahkan.
                </p>
            </div>

            <!-- Tombol buat unit baru (admin only) -->
            <div v-if="isAdmin && !showInlineCreate">
                <button
                    type="button"
                    @click="openInlineCreate"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 p-3 text-sm font-medium text-gray-500 transition-all hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-600"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Buat Unit Keluarga Baru
                </button>
            </div>

            <!-- Inline create form (admin only) -->
            <div
                v-if="isAdmin && showInlineCreate"
                class="space-y-3 rounded-xl border-2 border-indigo-300 bg-indigo-50 p-4"
            >
                <p class="text-sm font-semibold text-indigo-800">
                    Unit Keluarga Baru
                </p>
                <div class="flex gap-2">
                    <input
                        v-model="newUnitName"
                        type="text"
                        placeholder="Nama unit keluarga..."
                        maxlength="100"
                        :class="[
                            'flex-1 rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300',
                            createError
                                ? 'border-red-300 bg-red-50'
                                : 'border-gray-300 bg-white',
                        ]"
                        @keydown.enter.prevent="submitInlineCreate"
                    />
                    <button
                        type="button"
                        @click="submitInlineCreate"
                        :disabled="isCreating"
                        class="flex-shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ isCreating ? 'Membuat...' : 'Buat' }}
                    </button>
                    <button
                        type="button"
                        @click="cancelInlineCreate"
                        class="flex-shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                    >
                        Batal
                    </button>
                </div>
                <p v-if="createError" class="text-xs text-red-600">
                    ⚠ {{ createError }}
                </p>
            </div>

            <!-- Validation error -->
            <p
                v-if="errors.family_unit_id"
                class="flex items-center gap-1 text-sm text-red-600"
            >
                <span>⚠</span> {{ errors.family_unit_id }}
            </p>

            <!-- Selected summary -->
            <div
                v-if="selectedUnit"
                class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3"
            >
                <p class="text-sm text-indigo-700">
                    <span class="font-medium">Dipilih:</span>
                    {{ selectedUnit.name }}
                </p>
            </div>
        </div>

        <!-- ── SECTION RELASI (muncul setelah unit dipilih) ── -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-3"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div
                v-if="selectedUnit"
                class="space-y-4 border-t border-gray-100 pt-6"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">
                            Atur Relasi
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Opsional — hubungkan anggota baru ini dengan orang
                            lain di
                            <span class="font-medium">{{
                                selectedUnit.name
                            }}</span
                            >.
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-400"
                    >
                        {{ form.initial_relations?.length ?? 0 }} relasi
                    </span>
                </div>

                <!-- Tidak ada anggota di unit ini -->
                <div
                    v-if="peopleInUnit.length === 0"
                    class="rounded-xl border border-dashed border-gray-200 bg-gray-50 py-6 text-center text-sm text-gray-400"
                >
                    Belum ada anggota lain di unit ini. Relasi bisa diatur
                    setelah data disimpan.
                </div>

                <!-- Form tambah relasi -->
                <div v-else class="space-y-3">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <!-- Pilih anggota -->
                        <select
                            v-model="newRelation.related_id"
                            :class="[
                                'flex-1 rounded-lg border px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300',
                                relationError && !newRelation.related_id
                                    ? 'border-red-300 bg-red-50'
                                    : 'border-gray-300 bg-white',
                            ]"
                        >
                            <option value="">Pilih anggota...</option>
                            <option
                                v-for="p in peopleInUnit"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ genderIcon(p.gender) }} {{ p.display_name }}
                            </option>
                        </select>

                        <!-- Pilih jenis relasi -->
                        <select
                            v-model="newRelation.type"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 sm:w-52"
                        >
                            <option
                                v-for="t in relationTypes"
                                :key="t.value"
                                :value="t.value"
                            >
                                {{ t.label }}
                            </option>
                        </select>

                        <!-- Tombol tambah -->
                        <button
                            type="button"
                            @click="addRelation"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 sm:flex-shrink-0"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            Tambah
                        </button>
                    </div>

                    <!-- Checkbox biologis (hanya untuk relasi orang tua/anak) -->
                    <label
                        v-if="!['spouse'].includes(newRelation.type)"
                        class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-600"
                    >
                        <input
                            type="checkbox"
                            v-model="newRelation.is_biological"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        Hubungan biologis / kandung
                    </label>

                    <p v-if="relationError" class="text-xs text-red-600">
                        ⚠ {{ relationError }}
                    </p>
                </div>

                <!-- Daftar relasi yang sudah ditambahkan -->
                <div
                    v-if="form.initial_relations?.length > 0"
                    class="space-y-2"
                >
                    <p
                        class="text-xs font-medium uppercase tracking-wider text-gray-500"
                    >
                        Relasi yang akan dibuat
                    </p>
                    <div
                        v-for="(rel, idx) in form.initial_relations"
                        :key="idx"
                        class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3"
                    >
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ rel._label }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ rel._typeLabel }}
                                <span
                                    v-if="rel.type !== 'spouse'"
                                    class="ml-1 text-gray-400"
                                >
                                    ·
                                    {{
                                        rel.is_biological
                                            ? 'Biologis'
                                            : 'Non-biologis'
                                    }}
                                </span>
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="removeRelation(idx)"
                            class="flex-shrink-0 rounded p-1 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                            title="Hapus relasi"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <p class="text-xs text-gray-400">
                    💡 Relasi akan dibuat otomatis setelah data anggota
                    disimpan.
                </p>
            </div>
        </Transition>
    </div>
</template>
