<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    title: String,
    showHandle: { type: Boolean, default: true }
});

const emit = defineEmits(['close']);

const sheetRef = ref(null);
const startY = ref(0);
const currentY = ref(0);
const isDragging = ref(false);

function onTouchStart(e) {
    startY.value = e.touches[0].clientY;
    isDragging.value = true;
}

function onTouchMove(e) {
    if (!isDragging.value) return;

    const y = e.touches[0].clientY;
    const delta = y - startY.value;

    if (delta > 0) {
        currentY.value = delta;
    }
}

function onTouchEnd() {
    isDragging.value = false;

    if (currentY.value > 100) {
        emit('close');
    }

    currentY.value = 0;
}

function onOverlayClick() {
    emit('close');
}

// reset position when opened
watch(() => props.isOpen, (open) => {
    if (open) {
        currentY.value = 0;
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition name="sheet">
            <div v-if="isOpen" class="fixed inset-0 z-50">
                <!-- overlay -->
                <div class="absolute inset-0 bg-black/50" @click="onOverlayClick"></div>

                <!-- sheet -->
                <div ref="sheetRef"
                    class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-2xl max-h-[90vh] overflow-y-auto"
                    :style="{
                        transform: `translateY(${currentY}px)`,
                        transition: isDragging ? 'none' : 'transform 0.3s ease'
                    }" @touchstart="onTouchStart" @touchmove="onTouchMove" @touchend="onTouchEnd">
                    <!-- handle -->
                    <div v-if="showHandle" class="flex justify-center pt-3 pb-2 touch-none">
                        <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                    </div>

                    <!-- header -->
                    <div v-if="title" class="px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
                    </div>

                    <!-- content -->
                    <div class="p-4">
                        <slot />
                    </div>

                    <!-- footer -->
                    <div v-if="$slots.footer" class="p-4 border-t bg-gray-50">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.sheet-enter-active,
.sheet-leave-active {
    transition: opacity 0.3s ease;
}

.sheet-enter-from,
.sheet-leave-to {
    opacity: 0;
}

.sheet-enter-from>div:last-child,
.sheet-leave-to>div:last-child {
    transform: translateY(100%) !important;
}
</style>
