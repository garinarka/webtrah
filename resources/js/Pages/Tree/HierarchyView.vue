<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import * as d3 from 'd3';

const props = defineProps({
    rootId: { type: String, default: null },
    depthUp: { type: Number, default: 2 },
    depthDown: { type: Number, default: 2 },
});

const emit = defineEmits(['node-count']);

const container = ref(null);
const svgRef = ref(null);
const loading = ref(true);
const error = ref(null);
const nodeCount = ref(0);

let simulation = null;

// FETCH & RENDER

async function loadAndRender() {
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

        nodeCount.value = data.nodes.length;
        emit('node-count', data.nodes.length);

        loading.value = false;
        await nextTick();
        render(data);
    } catch (err) {
        error.value = 'Gagal memuat data pohon keluarga.';
        loading.value = false;
    }
}

watch(() => [props.rootId, props.depthUp, props.depthDown], loadAndRender);
onMounted(loadAndRender);
onUnmounted(() => { if (simulation) simulation.stop(); });

// D3 RENDER

function render(data) {
    if (!svgRef.value || !container.value) return;
    if (simulation) simulation.stop();

    const el = svgRef.value;
    const W = container.value.clientWidth || 900;
    const H = container.value.clientHeight || 580;
    const NODE_W = 130;
    const NODE_H = 56;
    const R = 8; // border-radius

    d3.select(el).selectAll('*').remove();

    const svg = d3.select(el)
        .attr('width', W)
        .attr('height', H);

    // defs: arrowhead
    const defs = svg.append('defs');
    defs.append('marker')
        .attr('id', 'arrow-parent')
        .attr('viewBox', '0 -4 8 8')
        .attr('refX', 8).attr('refY', 0)
        .attr('markerWidth', 6).attr('markerHeight', 6)
        .attr('orient', 'auto')
        .append('path').attr('d', 'M0,-4L8,0L0,4').attr('fill', '#94a3b8');

    defs.append('marker')
        .attr('id', 'arrow-spouse')
        .attr('viewBox', '0 -4 8 8')
        .attr('refX', 8).attr('refY', 0)
        .attr('markerWidth', 6).attr('markerHeight', 6)
        .attr('orient', 'auto')
        .append('path').attr('d', 'M0,-4L8,0L0,4').attr('fill', '#f472b6');

    const g = svg.append('g');

    // nodes & edges
    const nodes = data.nodes.map(d => ({ ...d }));
    const links = data.edges.map(d => ({
        ...d,
        source: nodes.find(n => n.id === d.source),
        target: nodes.find(n => n.id === d.target),
    })).filter(l => l.source && l.target);

    // force simulation
    simulation = d3.forceSimulation(nodes)
        .force('link', d3.forceLink(links).id(d => d.id).distance(d => d.type === 'spouse' ? 160 : 120).strength(0.8))
        .force('charge', d3.forceManyBody().strength(-600))
        .force('center', d3.forceCenter(W / 2, H / 2))
        .force('collide', d3.forceCollide(90))
        .force('y', d3.forceY().strength(0.15).y(d => {
            // push parent nodes ke atas, child ke bawah
            const isParentEdge = links.filter(l => l.type !== 'spouse');
            // heuristic: if node is source of parent edge -> higher up
            const asParent = isParentEdge.some(l => l.source.id === d.id);
            const asChild = isParentEdge.some(l => l.target.id === d.id);
            if (d.is_root) return H / 2;
            if (asParent && !asChild) return H / 2 - 180;
            if (asChild && !asParent) return H / 2 + 180;
            return H / 2;
        }));

    // links
    const link = g.append('g').selectAll('line')
        .data(links).enter().append('line')
        .attr('stroke', d => d.type === 'spouse' ? '#f9a8d4' : '#cbd5e1')
        .attr('stroke-width', d => d.type === 'spouse' ? 2 : 1.5)
        .attr('stroke-dasharray', d => d.type === 'spouse' ? '6,3' : null)
        .attr('marker-end', d => d.type === 'spouse' ? 'url(#arrow-spouse)' : 'url(#arrow-parent)');

    // node groups
    const node = g.append('g').selectAll('g')
        .data(nodes).enter().append('g')
        .style('cursor', 'pointer')
        .call(d3.drag()
            .on('start', (event, d) => {
                if (!event.active) simulation.alphaTarget(0.3).restart();
                d.fx = d.x; d.fy = d.y;
            })
            .on('drag', (event, d) => { d.fx = event.x; d.fy = event.y; })
            .on('end', (event, d) => {
                if (!event.active) simulation.alphaTarget(0);
                d.fx = null; d.fy = null;
            })
        )
        .on('click', (event, d) => {
            window.location.href = `/people/${d.id}`;
        });

    // card shadow filter
    defs.append('filter').attr('id', 'shadow')
        .append('feDropShadow')
        .attr('dx', 0).attr('dy', 2)
        .attr('stdDeviation', 3)
        .attr('flood-opacity', 0.12);

    // card background
    node.append('rect')
        .attr('width', NODE_W).attr('height', NODE_H)
        .attr('x', -NODE_W / 2).attr('y', -NODE_H / 2)
        .attr('rx', R).attr('ry', R)
        .attr('fill', d => {
            if (d.is_root) return '#4f46e5';
            if (d.status === 'pending') return '#fffbeb';
            return '#ffffff';
        })
        .attr('stroke', d => {
            if (d.is_root) return '#3730a3';
            if (d.status === 'pending') return '#fbbf24';
            if (d.gender === 'male') return '#bfdbfe';
            if (d.gender === 'female') return '#fbcfe8';
            return '#e5e7eb';
        })
        .attr('stroke-width', d => d.is_root ? 2.5 : 1.5)
        .attr('filter', 'url(#shadow)');

    // gender accent bar (left edge)
    node.append('rect')
        .attr('width', 4).attr('height', NODE_H - 2 * R)
        .attr('x', -NODE_W / 2).attr('y', -NODE_H / 2 + R)
        .attr('rx', 2)
        .attr('fill', d => {
            if (d.is_root) return '#818cf8';
            if (d.gender === 'male') return '#60a5fa';
            if (d.gender === 'female') return '#f472b6';
            return '#d1d5db';
        });

    // name text
    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', NODE_H / 2 > 28 ? '-6px' : '0')
        .attr('font-size', '11px')
        .attr('font-weight', d => d.is_root ? '700' : '500')
        .attr('fill', d => d.is_root ? '#e0e7ff' : '#1f2937')
        .text(d => {
            const n = d.name || 'Unknown';
            return n.length > 18 ? n.slice(0, 17) + '…' : n;
        });

    // birth/death year text
    node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '14px')
        .attr('font-size', '10px')
        .attr('fill', d => d.is_root ? '#a5b4fc' : '#9ca3af')
        .text(d => {
            if (d.birth_year && d.death_year) return `${d.birth_year} – ${d.death_year}`;
            if (d.birth_year) return `${d.birth_year}`;
            return '';
        });

    // simulation tick
    simulation.on('tick', () => {
        link
            .attr('x1', d => d.source.x)
            .attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x)
            .attr('y2', d => d.target.y);

        node.attr('transform', d => `translate(${d.x},${d.y})`);
    });

    // zoom & pan
    const zoom = d3.zoom()
        .scaleExtent([0.2, 3])
        .on('zoom', (event) => g.attr('transform', event.transform));

    svg.call(zoom)
        .call(zoom.transform, d3.zoomIdentity.translate(0, 0).scale(1));
}
</script>

<template>
    <div ref="container" class="w-full h-full relative" style="min-height: 500px;">
        <!-- loading -->
        <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
            <div class="flex flex-col items-center gap-3 text-gray-500">
                <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="text-sm">Memuat pohon keluarga…</span>
            </div>
        </div>

        <!-- no root -->
        <div v-else-if="!rootId" class="absolute inset-0 flex items-center justify-center">
            <div class="text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-sm font-medium">Pilih anggota keluarga</p>
                <p class="text-xs mt-1">Gunakan pencarian di atas untuk memilih titik awal pohon</p>
            </div>
        </div>

        <!-- error -->
        <div v-else-if="error" class="absolute inset-0 flex items-center justify-center">
            <div class="text-center text-red-400">
                <p class="text-sm font-medium">{{ error }}</p>
                <button @click="loadAndRender" class="mt-2 text-xs text-indigo-500 hover:text-indigo-700">
                    Coba lagi
                </button>
            </div>
        </div>

        <!-- empty -->
        <div v-else-if="nodeCount === 0" class="absolute inset-0 flex items-center justify-center">
            <div class="text-center text-gray-400">
                <p class="text-sm">Tidak ada data relasi untuk ditampilkan.</p>
                <p class="text-xs mt-1">Tambahkan relasi antar anggota terlebih dahulu.</p>
            </div>
        </div>

        <!-- svg canvas -->
        <svg v-show="!loading && !error && nodeCount > 0" ref="svgRef" class="w-full h-full block"
            style="min-height:500px;" />

        <!-- legend -->
        <div v-show="!loading && nodeCount > 0"
            class="absolute bottom-4 left-4 bg-white/95 backdrop-blur border border-gray-200 p-3 rounded-xl shadow-sm text-xs space-y-1.5 z-10">
            <p class="font-semibold text-gray-700 mb-1">Keterangan</p>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-indigo-600 flex-shrink-0" /><span>Titik
                    awal (root)</span></div>
            <div class="flex items-center gap-2"><span
                    class="w-3 h-1 border-t-2 border-slate-300 flex-shrink-0" /><span>Hubungan orang tua–anak</span>
            </div>
            <div class="flex items-center gap-2"><span
                    class="w-3 h-1 border-t-2 border-dashed border-pink-300 flex-shrink-0" /><span>Hubungan
                    pasangan</span></div>
            <div class="flex items-center gap-2"><span
                    class="w-1 h-3 rounded bg-blue-400 flex-shrink-0" /><span>Laki-laki</span></div>
            <div class="flex items-center gap-2"><span
                    class="w-1 h-3 rounded bg-pink-400 flex-shrink-0" /><span>Perempuan</span></div>
            <p class="text-gray-400 pt-1 border-t border-gray-100">Scroll zoom · Drag geser · Klik detail</p>
        </div>

        <!-- node count badge -->
        <div v-if="nodeCount > 0"
            class="absolute top-3 right-3 bg-white/90 border border-gray-200 text-xs text-gray-500 px-2 py-1 rounded-lg">
            {{ nodeCount }} anggota ditampilkan
        </div>
    </div>
</template>
