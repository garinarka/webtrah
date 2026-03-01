<script setup>
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import HierarchyView from './HierarchyView.vue';
import TimelineView from './TimelineView.vue';

const props = defineProps({
    people: { type: Array, default: () => [] },
});

// MODE
const currentMode = ref('hierarchy');
const modes = [
    { id: 'hierarchy', name: 'Pohon' },
    { id: 'timeline', name: 'Timeline' },
];

// ROOT PERSON SELECTOR
const searchQuery = ref('');
const searchResults = ref(props.people.slice(0, 8));
const selectedPerson = ref(null);
const showDropdown = ref(false);
let searchTimer;

const selectPerson = (person) => {
    selectedPerson.value = person;
    searchQuery.value = person.display_name;
    showDropdown.value = false;
};

watch(searchQuery, (q) => {
    if (selectedPerson.value && q !== selectedPerson.value.display_name) {
        selectedPerson.value = null;
    }
    clearTimeout(searchTimer);
    showDropdown.value = true;
    if (!q) {
        searchResults.value = props.people.slice(0, 8);
        return;
    }
    searchTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/tree/search?q=${encodeURIComponent(q)}`);
            searchResults.value = await res.json();
        } catch {
            // fallback: filter local
            searchResults.value = props.people
                .filter(p => p.display_name.toLowerCase().includes(q.toLowerCase()))
                .slice(0, 8);
        }
    }, 250);
});

// DEPTH CONTROLS
const depthUp = ref(2);
const depthDown = ref(2);

// NODE COUNT
const nodeCount = ref(0);

// HELPERS
const genderBg = (g) => ({
    male: 'bg-blue-100 text-blue-700',
    female: 'bg-pink-100 text-pink-700',
    unknown: 'bg-gray-100 text-gray-500',
}[g] ?? 'bg-gray-100 text-gray-500');

const formatYear = (d) => d ? new Date(d).getFullYear() : null;

// close dropdown on outside click
const onBlur = () => setTimeout(() => { showDropdown.value = false; }, 150);
</script>

<template>

    <Head title="Pohon Keluarga" />
    <AppLayout>
        <div class="flex flex-col gap-4" style="height: calc(100vh - 120px); min-height: 600px;">

            <!-- top toolbar -->
            <div class="flex flex-wrap items-center gap-3 flex-shrink-0">

                <!-- title -->
                <div class="mr-2">
                    <h1 class="text-xl font-semibold text-gray-950 leading-none">Pohon Keluarga</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ nodeCount > 0 ? `${nodeCount} anggota ditampilkan` :
                        'Pilih anggota untuk memulai' }}</p>
                </div>

                <!-- root person search -->
                <div class="relative flex-1 min-w-[220px] max-w-xs">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-5.197-5.197A7.5 7.5 0 105.196 5.196 7.5 7.5 0 0016.803 15.803z" />
                        </svg>
                        <input v-model="searchQuery" type="text" placeholder="Cari anggota (titik awal)..."
                            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400"
                            @focus="showDropdown = true" @blur="onBlur" />
                    </div>

                    <!-- dropdown -->
                    <div v-if="showDropdown && searchResults.length > 0"
                        class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden max-h-60 overflow-y-auto">
                        <button v-for="p in searchResults" :key="p.id" @mousedown.prevent="selectPerson(p)"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-gray-50 transition-colors text-left">
                            <div
                                :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0', genderBg(p.gender)]">
                                {{ p.display_name.charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ p.display_name }}</p>
                                <p class="text-xs text-gray-400">{{ formatYear(p.birth_date) ?? 'Tahun ?' }}</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- depth controls -->
                <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5">
                    <span class="text-xs text-gray-500 font-medium">Atas</span>
                    <div class="flex items-center gap-1">
                        <button @click="depthUp = Math.max(0, depthUp - 1)"
                            class="w-6 h-6 rounded flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors font-bold text-sm">−</button>
                        <span class="w-5 text-center text-sm font-semibold text-gray-700 tabular-nums">{{ depthUp
                        }}</span>
                        <button @click="depthUp = Math.min(4, depthUp + 1)"
                            class="w-6 h-6 rounded flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors font-bold text-sm">+</button>
                    </div>
                    <span class="text-gray-300 mx-1">|</span>
                    <span class="text-xs text-gray-500 font-medium">Bawah</span>
                    <div class="flex items-center gap-1">
                        <button @click="depthDown = Math.max(0, depthDown - 1)"
                            class="w-6 h-6 rounded flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors font-bold text-sm">−</button>
                        <span class="w-5 text-center text-sm font-semibold text-gray-700 tabular-nums">{{ depthDown
                        }}</span>
                        <button @click="depthDown = Math.min(4, depthDown + 1)"
                            class="w-6 h-6 rounded flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors font-bold text-sm">+</button>
                    </div>
                </div>

                <!-- mode toggle -->
                <div class="flex bg-gray-100 rounded-lg p-1 flex-shrink-0 ml-auto">
                    <button v-for="mode in modes" :key="mode.id" @click="currentMode = mode.id" :class="[
                        'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                        currentMode === mode.id
                            ? 'bg-white text-indigo-600 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700',
                    ]">
                        {{ mode.name }}
                    </button>
                </div>
            </div>

            <!-- canvas -->
            <div class="flex-1 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden relative">
                <KeepAlive>
                    <HierarchyView v-if="currentMode === 'hierarchy'" :root-id="selectedPerson?.id ?? null"
                        :depth-up="depthUp" :depth-down="depthDown" @node-count="nodeCount = $event" />
                    <TimelineView v-else :root-id="selectedPerson?.id ?? null" :depth-up="depthUp"
                        :depth-down="depthDown" @node-count="nodeCount = $event" />
                </KeepAlive>
            </div>

        </div>
    </AppLayout>
</template>
