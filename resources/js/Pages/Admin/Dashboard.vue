<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    stats: Object,
});
</script>

<template>
    <AdminLayout title="Admin Dashboard">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Dashboard</h2>
        </template>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Users</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_users }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Shops</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_shops }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Pending Shops</p>
                <p class="text-2xl font-bold text-yellow-600 mt-1">{{ stats.pending_shops }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Products</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_products }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Orders</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_orders }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                <p class="text-2xl font-bold text-green-600 mt-1">${{ Number(stats.total_revenue).toFixed(2) }}</p>
            </div>
        </div>
    </AdminLayout>
</template>