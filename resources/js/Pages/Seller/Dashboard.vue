<script setup>
import { Link } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';

defineProps({
    shop: Object,
    stats: Object,
    recentOrders: Array,
});
</script>

<template>
    <SellerLayout title="Seller Dashboard">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Dashboard</h2>
        </template>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Products</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total_products }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Active Products</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.active_products }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Pending Orders</p>
                <p class="text-2xl font-bold text-yellow-600 mt-1">{{ stats.pending_orders }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ Number(stats.total_revenue).toFixed(2) }}</p>
            </div>
        </div>

        <!-- Shop Status -->
        <div v-if="shop.status === 'pending'" class="mb-6 rounded-md bg-yellow-50 border border-yellow-200 p-4">
            <p class="text-sm text-yellow-800">Your shop is pending approval. Some features may be limited.</p>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Recent Orders</h3>
                <Link :href="route('seller.orders.index')" class="text-sm text-indigo-600 hover:text-indigo-800">View all</Link>
            </div>
            <div v-if="recentOrders.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                <Link
                    v-for="order in recentOrders"
                    :key="order.id"
                    :href="route('seller.orders.show', order.id)"
                    class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ order.order_number }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ new Date(order.created_at).toLocaleDateString() }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ Number(order.total).toFixed(2) }}</span>
                        <OrderStatusBadge :status="order.status" />
                    </div>
                </Link>
            </div>
            <div v-else class="px-6 py-8 text-center text-gray-500">
                No orders yet.
            </div>
        </div>
    </SellerLayout>
</template>