<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { usePreferencesStore } from '@/stores/preferences';
import TopBar from '@/components/layout/TopBar.vue';
import Sidebar from '@/components/layout/Sidebar.vue';
import BottomNav from '@/components/layout/BottomNav.vue';

const page = usePage();
const auth = useAuthStore();
const ui = useUiStore();
const preferences = usePreferencesStore();

auth.initializeFromPage(page.props.auth);
onMounted(() => { preferences.loadFromStorage(); window.addEventListener('resize', ui.updateIsMobile); });
onUnmounted(() => { window.removeEventListener('resize', ui.updateIsMobile); });

const navigationItems = computed(() => {
    const items = [
        { name: 'Beranda', href: '/dashboard', icon: 'Home' },
        { name: 'Pohon', href: '/tree', icon: 'Tree' },
    ];
    if (auth.isModerator) items.push({ name: 'Persetujuan', href: '/approvals', icon: 'Clipboard', badge: 3 });
    if (auth.isAdmin) items.push({ name: 'Laporan', href: '/reports', icon: 'Chart' });
    return items;
});

const bodyClasses = computed(() => Object.entries(preferences.bodyClasses).filter(([, v]) => v).map(([k]) => k).join(' '));
</script>

<template>
    <div :class="['min-h-screen bg-gray-50', bodyClasses]">
        <TopBar :user="auth.user" :notifications="[]" />
        <div class="flex pt-16">
            <Sidebar v-if="!ui.isMobile" :items="navigationItems" :open="ui.sidebarOpen" @toggle="ui.toggleSidebar" />
            <main class="flex-1 transition-all duration-200"
                :class="{ 'ml-64': !ui.isMobile && ui.sidebarOpen, 'ml-16': !ui.isMobile && !ui.sidebarOpen, 'pb-20': ui.isMobile }">
                <div class="p-4 md:p-6 lg:p-8">
                    <slot />
                </div>
            </main>
        </div>
        <BottomNav v-if="ui.isMobile" :items="navigationItems" />
    </div>
</template>
