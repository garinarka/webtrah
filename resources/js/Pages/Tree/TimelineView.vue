<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    rootId: { type: String, default: null },
    depthUp: { type: Number, default: 2 },
    depthDown: { type: Number, default: 2 },
});

const emit = defineEmits(['node-count']);

const generations = ref([]);
const loading = ref(true);
const error = ref(null);

async function load() {
    if (!props.rootId) { loading.value = false; return; }

    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams({
            root_id: props.rootId,
            depth_up: props.depthUp,
            depth_down: props.depthDown,
        });
        const res = await fetch(`/api/tree/data?${params}`);
        const data = await res.json();

        emit('node-count', data.nodes.length);
        generations.value = groupByGeneration(data);
        loading.value = false;
    } catch {
        error.value = 'Gagal memuat data timeline.';
        loading.value = false;
    }
}

watch(() => [props.rootId, props.depthUp, props.depthDown], load);
onMounted(load);

function groupByGeneration(data) {
    if (!data.nodes.length) return [];

    // build parent map: child -> [parents]
    const parentMap = {};
    data.edges.forEach(e => {
        if (e.type !== 'spouse') {
            if (!parentMap[e.target]) parentMap[e.target] = [];
            parentMap[e.target].push(e.source);
        }
    });

    // bfs assign generation level: root = 0
    const rootId = data.root_id;
    const levels = {};
    const queue = [{ id: rootId, level: 0 }];
    const visited = new Set();

    while (queue.length) {
        const { id, level } = queue.shift();
        if (visited.has(id)) continue;
        visited.add(id);
        levels[id] = level;

        // parents -> higher level (negative)
        (parentMap[id] || []).forEach(pid => {
            if (!visited.has(pid)) queue.push({ id: pid, level: level - 1 });
        });

        // children -> lower level (positive)
        data.edges
            .filter(e => e.source === id && e.type !== 'spouse')
            .forEach(e => {
                if (!visited.has(e.target)) queue.push({ id: e.target, level: level + 1 });
            });
    }

    // group by level
    const groups = {};
    data.nodes.forEach(n => {
        const lv = levels[n.id] ?? 0;
        if (!groups[lv]) groups[lv] = [];
        groups[lv].push(n);
    });

    const sortedLevels = Object.keys(groups).map(Number).sort((a, b) => a - b);

    const genLabels = (lv) => {
        if (lv < -3) return `Leluhur (${Math.abs(lv)} generasi ke atas)`;
        return { '-3': 'Buyut', '-2': 'Kakek / Nenek', '-1': 'Orang Tua', '0': 'Generasi Utama', '1': 'Anak', '2': 'Cucu', '3': 'Cicit' }[lv] ?? `Generasi +${lv}`;
    };

    return sortedLevels.map(lv => ({
        level: lv,
        label: genLabels(lv),
        members: groups[lv].sort((a, b) => (a.birth_year ?? 9999) - (b.birth_year ?? 9999)),
    }));
}

const genderBg = (g) => ({ male: 'bg-blue-50 border-blue-200', female: 'bg-pink-50 border-pink-200' }[g] ?? 'bg-gray-50 border-gray-200');
const genderDot = (g) => ({ male: 'bg-blue-400', female: 'bg-pink-400' }[g] ?? 'bg-gray-400');
</script>

<template>
    <div class="w-full h-full overflow-y-auto p-6">
        <!-- loading -->
        <div v-if="loading" class="flex items-center justify-center py-20 text-gray-400">
            <svg class="animate-spin w-6 h-6 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <span class="text-sm">Memuat timeline…</span>
        </div>

        <!-- no root -->
        <div v-else-if="!rootId" class="flex items-center justify-center py-20 text-gray-400">
            <p class="text-sm">Pilih anggota keluarga untuk memulai.</p>
        </div>

        <!-- error -->
        <div v-else-if="error" class="flex items-center justify-center py-20 text-red-400">
            <p class="text-sm">{{ error }}</p>
        </div>

        <!-- empty -->
        <div v-else-if="generations.length === 0" class="flex items-center justify-center py-20 text-gray-400">
            <p class="text-sm">Tidak ada data relasi untuk ditampilkan.</p>
        </div>

        <!-- timeline -->
        <div v-else class="space-y-8 max-w-5xl">
            <div v-for="gen in generations" :key="gen.level" class="flex gap-6 items-start">
                <!-- level label -->
                <div class="w-36 flex-shrink-0 pt-2 text-right">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ gen.label }}</p>
                    <p class="text-xs text-gray-300 mt-0.5">{{ gen.members.length }} orang</p>
                </div>

                <!-- connector -->
                <div class="flex-shrink-0 flex flex-col items-center pt-4">
                    <div class="w-2 h-2 rounded-full" :class="gen.level === 0 ? 'bg-indigo-500' : 'bg-gray-300'" />
                    <div class="w-px flex-1 bg-gray-200 mt-1" />
                </div>

                <!-- members -->
                <div class="flex flex-wrap gap-3 pb-6">
                    <a v-for="m in gen.members" :key="m.id" :href="`/people/${m.id}`" :class="[
                        'flex items-start gap-2.5 w-44 p-3 border rounded-xl hover:shadow-md transition-all text-left',
                        m.is_root ? 'bg-indigo-600 border-indigo-700 text-white' : genderBg(m.gender),
                        m.status === 'pending' ? 'opacity-60' : '',
                    ]">
                        <div
                            :class="['w-2 h-full min-h-[2.5rem] rounded-full flex-shrink-0 mt-0.5', m.is_root ? 'bg-indigo-300' : genderDot(m.gender)]" />
                        <div class="min-w-0">
                            <p
                                :class="['text-sm font-semibold leading-tight truncate', m.is_root ? 'text-white' : 'text-gray-900']">
                                {{ m.name }}
                            </p>
                            <p :class="['text-xs mt-0.5', m.is_root ? 'text-indigo-200' : 'text-gray-400']">
                                <template v-if="m.birth_year && m.death_year">{{ m.birth_year }} – {{ m.death_year
                                }}</template>
                                <template v-else-if="m.birth_year">b. {{ m.birth_year }}</template>
                                <template v-else>Tahun tidak diketahui</template>
                            </p>
                            <span v-if="m.status === 'pending'"
                                class="inline-block mt-1 text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">
                                Pending
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
