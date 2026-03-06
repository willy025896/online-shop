<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    orders: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
</script>

<template>
    <AdminLayout :title="lang.orders?.title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.orders?.title }}</h2>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table v-if="orders.data.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.order }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.customer }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.shop }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.total }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.status }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.date }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ order.order_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ order.user?.name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ order.shop?.name }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">${{ Number(order.total).toFixed(2) }}</td>
                        <td class="px-6 py-4"><OrderStatusBadge :status="order.status" /></td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-gray-500">
                {{ lang.orders?.no_orders }}
            </div>
        </div>

        <div class="mt-6">
            <Pagination :links="orders.links" />
        </div>
    </AdminLayout>
</template>
