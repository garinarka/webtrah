<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
import AdminDashboard from './Admin.vue';
import ModeratorDashboard from './Moderator.vue';
import UserDashboard from './User.vue';

const page = usePage();
const auth = useAuthStore();
auth.initializeFromPage(page.props.auth);

const dashboardComponent = computed(() => {
    if (auth.isAdmin) return AdminDashboard;
    if (auth.isModerator) return ModeratorDashboard;
    return UserDashboard;
});
</script>

<template>
    <AppLayout>
        <component :is="dashboardComponent" :stats="$page.props.stats" />
    </AppLayout>
</template>
