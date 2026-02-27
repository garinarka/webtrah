<script setup>
import { ref, onMounted } from 'vue';

const generations = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch('/api/tree/data?depth=3');
        const data = await response.json();

        // group by generation (simplified)
        generations.value = groupByGeneration(data);
        loading.value = false;
    } catch (err) {
        loading.value = false;
    }
});

function groupByGeneration(data) {
    // simplified: assume birth year indicates generation
    const nodes = data.nodes.sort((a, b) => (a.year || 0) - (b.year || 0));

    const gens = [];
    let currentGen = [];
    let lastYear = null;

    nodes.forEach(node => {
        if (!lastYear || Math.abs((node.year || 0) - lastYear) > 25) {
            if (currentGen.length) gens.push(currentGen);
            currentGen = [];
        }
        currentGen.push(node);
        lastYear = node.year || lastYear;
    });

    if (currentGen.length) gens.push(currentGen);

    return gens.map((gen, i) => ({
        level: i + 1,
        label: i === 0 ? 'Buyut' : i === 1 ? 'Kakek/Nenek' : i === 2 ? 'Orang Tua' : 'Anak/Cucu',
        members: gen
    }));
}
</script>

<template>
    <div class="w-full h-full overflow-y-auto p-6">
        <div v-if="loading" class="flex items-center justify-center h-full">
            <div class="text-gray-500">Memuat timeline...</div>
        </div>

        <div v-else class="space-y-8">
            <div v-for="gen in generations" :key="gen.level" class="relative">
                <!-- generation label -->
                <div class="flex items-center mb-4">
                    <div class="w-24 text-sm font-medium text-gray-500">
                        Gen {{ gen.level }}
                    </div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ gen.label }}
                    </div>
                </div>

                <!-- members -->
                <div class="ml-24 flex flex-wrap gap-4">
                    <a v-for="member in gen.members" :key="member.id" :href="`/people/${member.id}`"
                        class="block w-40 p-4 bg-white border rounded-lg hover:shadow-md transition-shadow" :class="{
                            'border-blue-200 bg-blue-50': member.gender === 'male',
                            'border-pink-200 bg-pink-50': member.gender === 'female',
                            'border-amber-200 bg-amber-50': member.status === 'pending'
                        }">
                        <div class="font-medium text-gray-900 truncate">{{ member.name }}</div>
                        <div class="text-sm text-gray-500">{{ member.year || '?' }}</div>
                    </a>
                </div>

                <!-- connector line -->
                <div v-if="gen.level < generations.length" class="absolute left-12 top-full h-8 w-0.5 bg-gray-300">
                </div>
            </div>
        </div>
    </div>
</template>
