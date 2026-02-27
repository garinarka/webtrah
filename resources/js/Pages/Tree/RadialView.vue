<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import * as d3 from 'd3';

const container = ref(null);
const svg = ref(null);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
    try {
        const response = await fetch('/api/tree/data?depth=2');
        const data = await response.json();

        loading.value = false;
        await nextTick();
        renderRadial(data);

    } catch (err) {
        console.error('Fetch error:', err);
        error.value = 'Gagal memuat data pohon';
        loading.value = false;
    }
});

onUnmounted(() => {
    d3.select(svg.value).selectAll('*').remove();
});

function renderRadial(data) {
    if (!svg.value) {
        console.error('SVG ref not available');
        return;
    }

    const width = container.value?.clientWidth || 800;
    const height = container.value?.clientHeight || 600;
    const radius = Math.min(width, height) / 2 - 80;

    // clear previous
    const svgEl = d3.select(svg.value)
        .attr('width', width)
        .attr('height', height);

    svgEl.selectAll('*').remove();

    const g = svgEl.append('g');

    // color scale
    const color = {
        male: '#3b82f6',
        female: '#ec4899',
        unknown: '#9ca3af',
        pending: '#f59e0b'
    };

    try {
        // build hierarchy
        const nodeWithParent = new Set(data.edges.map(e => e.target));
        const parentMap = {};
        data.edges.forEach(e => {
            parentMap[e.target] = e.source;
        });

        const root = d3.stratify()
            .id(d => d.id)
            .parentId(d => parentMap[d.id] || null)
            (data.nodes);

        // tree layout
        const tree = d3.tree()
            .size([2 * Math.PI, radius])
            .separation((a, b) => (a.parent === b.parent ? 1 : 2) / a.depth);

        tree(root);

        // links
        g.selectAll('.link')
            .data(root.links())
            .enter()
            .append('path')
            .attr('class', 'link')
            .attr('d', d3.linkRadial()
                .angle(d => d.x)
                .radius(d => d.y))
            .attr('fill', 'none')
            .attr('stroke', '#cbd5e1')
            .attr('stroke-width', 1.5);

        // nodes
        const node = g.selectAll('.node')
            .data(root.descendants())
            .enter()
            .append('g')
            .attr('class', 'node')
            .attr('transform', d => `
                rotate(${(d.x * 180 / Math.PI) - 90})
                translate(${d.y},0)
            `);

        // node circles
        node.append('circle')
            .attr('r', d => d.depth === 0 ? 14 : 9)
            .attr('fill', d => color[d.data.status === 'pending' ? 'pending' : d.data.gender] || color.unknown)
            .attr('stroke', '#fff')
            .attr('stroke-width', 2.5)
            .style('cursor', 'pointer')
            .on('click', (event, d) => {
                window.location.href = `/people/${d.data.id}`;
            });

        // labels (show more levels for better UX)
        node.filter(d => d.depth <= 2)
            .append('text')
            .attr('dy', '0.31em')
            .attr('x', d => d.x < Math.PI === !d.children ? 12 : -12)
            .attr('text-anchor', d => d.x < Math.PI === !d.children ? 'start' : 'end')
            .attr('transform', d => d.x >= Math.PI ? 'rotate(180)' : null)
            .text(d => {
                const name = d.data.name || 'Unknown';
                return name.length > 20 ? name.substring(0, 20) + '...' : name;
            })
            .attr('font-size', d => d.depth === 0 ? '13px' : d.depth === 1 ? '11px' : '9px')
            .attr('font-weight', d => d.depth === 0 ? 'bold' : 'normal')
            .attr('fill', '#374151')
            .style('pointer-events', 'none'); // prevent text from blocking clicks

        // zoom
        const zoom = d3.zoom()
            .scaleExtent([0.3, 4])
            .on('zoom', (event) => {
                g.attr('transform', event.transform.toString());
            });

        svgEl.call(zoom)
            .call(zoom.transform, d3.zoomIdentity.translate(width / 2, height / 2));

    } catch (err) {
        console.error('D3 Render Error:', err);
        error.value = 'Gagal render pohon: ' + err.message;
    }
}
</script>

<template>
    <div ref="container" class="w-full h-full relative" style="min-height: 500px;">
        <!-- loading -->
        <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white">
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-8 w-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span class="text-gray-500">Memuat pohon keluarga...</span>
            </div>
        </div>

        <!-- error -->
        <div v-else-if="error" class="absolute inset-0 flex items-center justify-center bg-white">
            <div class="text-center">
                <div class="text-red-500 mb-2">{{ error }}</div>
                <button @click="window.location.reload()" class="text-sm text-indigo-600 hover:text-indigo-800">
                    Coba lagi
                </button>
            </div>
        </div>

        <!-- svg -->
        <svg v-show="!loading && !error" ref="svg" class="w-full h-full block" style="min-height: 500px;">
        </svg>

        <!-- legend -->
        <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur p-3 rounded-lg shadow text-xs space-y-2 z-10">
            <div class="font-medium text-gray-700 mb-1">Keterangan:</div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                <span>Laki-laki</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-pink-500 mr-2"></span>
                <span>Perempuan</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-amber-500 mr-2"></span>
                <span>Pending</span>
            </div>
            <div class="mt-2 pt-2 border-t text-gray-500">
                Scroll untuk zoom • Drag untuk geser • Klik untuk detail
            </div>
        </div>
    </div>
</template>
