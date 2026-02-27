<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    actions: Array
});

const auth = useAuthStore();
const isExpanded = ref(false);

// filter actions based on permission
const visibleActions = computed(() => {
    return props.actions.filter(action => {
        if (!action.permission) return true;

        // check permission
        return auth.can(action.permission);
    });
});

const hasVisibleActions = computed(() => visibleActions.value.length > 0);

function handleAction(action) {
    isExpanded.value = false;
    if (typeof action.onClick === 'function') {
        action.onClick();
    }
}

function toggle() {
    isExpanded.value = !isExpanded.value;
}
</script>

<template>
    <div v-if="hasVisibleActions" class="fixed bottom-20 sm:bottom-5 right-4 z-40 flex flex-col items-end">
        <!-- action buttons -->
        <TransitionGroup name="fab">
            <button v-for="(action, index) in visibleActions" v-show="isExpanded" :key="action.id"
                @click="handleAction(action)"
                class="mb-3 flex items-center bg-white rounded-full shadow-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all"
                :style="{ transitionDelay: isExpanded ? `${index * 50}ms` : '0ms' }">
                <span class="mr-2">{{ action.label }}</span>
                <span class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-lg">
                    {{ action.icon }}
                </span>
            </button>
        </TransitionGroup>

        <!-- main fab -->
        <button @click="toggle"
            class="w-14 h-14 rounded-full bg-indigo-600 text-white shadow-lg flex items-center justify-center text-3xl hover:bg-indigo-700 transition-all"
            :class="{ 'rotate-45': isExpanded }">
            {{ isExpanded ? '×' : '+' }}
        </button>
    </div>
</template>

<style scoped>
.fab-enter-active,
.fab-leave-active {
    transition: all 0.2s ease;
}

.fab-enter-from,
.fab-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
