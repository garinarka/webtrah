<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useAuthStore } from '@/stores/auth';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ModeratorLayout from '@/layouts/ModeratorLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import AdminDashboard from './Admin.vue';
import ModeratorDashboard from './Moderator.vue';
import UserDashboard from './User.vue';

const page = usePage();
const auth = useAuthStore();
auth.initializeFromPage(page.props.auth);

const currentLayout = computed(() => {
    if (auth.isAdmin) return AdminLayout;
    if (auth.isModerator) return ModeratorLayout;
    return UserLayout;
});

const dashboardComponent = computed(() => {
    if (auth.isAdmin) return AdminDashboard;
    if (auth.isModerator) return ModeratorDashboard;
    return UserDashboard;
});
</script>

<template>
    <component :is="currentLayout">
        <component :is="dashboardComponent" />
    </component>
</template>
