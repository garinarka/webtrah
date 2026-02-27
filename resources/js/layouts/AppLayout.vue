<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { usePreferencesStore } from '@/stores/preferences';
import TopBar from '@/components/layout/TopBar.vue';
import Sidebar from '@/components/layout/Sidebar.vue';
import BottomNav from '@/components/layout/BottomNav.vue';
import BottomSheet from '@/components/layout/BottomSheet.vue';
import Fab from '@/components/ui/Fab.vue';
import SeniorModeToggle from '@/features/accessibility/components/SeniorModeToggle.vue';

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
        { name: 'Anggota', href: '/people', icon: 'Users' },
        { name: 'Pohon', href: '/tree', icon: 'Tree' },
    ];
    if (auth.isModerator) items.push({ name: 'Persetujuan', href: '/approvals', icon: 'Clipboard', badge: 3 });
    if (auth.isAdmin) items.push({ name: 'Laporan', href: '/reports', icon: 'Chart' });
    return items;
});

const bodyClasses = computed(() => {
    const classes = [];

    if (preferences.seniorMode) {
        classes.push('senior-mode');
        classes.push(`font-size-${preferences.fontSize}`);
    }

    if (preferences.highContrast) classes.push('high-contrast');
    if (preferences.reducedMotion) classes.push('reduced-motion');

    return classes.join(' ');
});

// fab actions for moderator
const fabActions = computed(() => {
    const actions = [];

    if (auth.can('create_person')) {
        actions.push({
            id: 'person',
            label: 'Tambah Anggota',
            icon: '👤',
            permission: 'create_person',
            onClick: () => window.location.href = '/people/create'
        });
    }

    if (auth.can('create_person')) {
        actions.push({
            id: 'event',
            label: 'Tambah Peristiwa',
            icon: '📅',
            permission: 'create_person',
            onClick: () => {
                alert('Fitur dalam pengembangan');
            }
        });
    }

    return actions;
});

// sheet content
const sheetContent = computed(() => {
    switch (ui.activeSheet) {
        case 'senior-mode':
            return { title: 'Pengaturan Tampilan', component: SeniorModeToggle };
        case 'event':
            return { title: 'Tambah Peristiwa', component: null };
        default:
            return null;
    }
});
</script>

<template>
    <div :class="['min-h-screen bg-gray-50', bodyClasses]">
        <TopBar :user="auth.user" :notifications="[]" />

        <!-- senior mode quick toggle -->
        <button @click="ui.openSheet('senior-mode')"
            class="fixed top-20 right-4 z-30 bg-white p-2 rounded-full shadow text-xl" title="Mode Lansia">
            &#x1F474;
        </button>

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

        <!-- fab for moderator/user -->
        <Fab v-if="auth.isModerator || auth.isUser" :actions="fabActions" />

        <!-- bottom sheet -->
        <BottomSheet :is-open="!!ui.activeSheet" :title="sheetContent?.title" @close="ui.closeSheet">
            <component :is="sheetContent?.component" v-if="sheetContent?.component" />
            <div v-else class="text-gray-500 text-center py-8">
                Fitur dalam pengembangan
            </div>
        </BottomSheet>
    </div>
</template>
