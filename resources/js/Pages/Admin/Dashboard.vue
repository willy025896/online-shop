<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    stats: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
</script>

<template>
    <AdminLayout :title="lang.dashboard">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.dashboard }}</h2>
        </template>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.total_users }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_users }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.total_shops }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_shops }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.pending_shops }}</p>
                <p class="text-2xl font-bold text-yellow-600 mt-1">{{ stats.pending_shops }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.total_products }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_products }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.total_orders }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_orders }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.total_revenue }}</p>
                <p class="text-2xl font-bold text-green-600 mt-1">${{ Number(stats.total_revenue).toFixed(2) }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
