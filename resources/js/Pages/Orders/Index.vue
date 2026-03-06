<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    orders: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
</script>

<template>
    <AppLayout :title="lang.title">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ lang.title }}</h1>

            <div v-if="orders.data.length" class="space-y-4">
                <Link
                    v-for="order in orders.data"
                    :key="order.id"
                    :href="route('orders.show', order.id)"
                    class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-md transition"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ order.order_number }}</p>
                            <p class="text-xs text-gray-500">{{ new Date(order.created_at).toLocaleDateString() }}</p>
                        </div>
                        <OrderStatusBadge :status="order.status" />
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ (lang.items || ':count item(s)').replace(':count', order.items?.length) }}</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">${{ Number(order.total).toFixed(2) }}</p>
                    </div>
                </Link>
            </div>

            <div v-else class="text-center py-16 text-gray-500">
                {{ lang.no_orders }}
            </div>

            <div class="mt-8">
                <Pagination :links="orders.links" />
            </div>
        </div>
    </AppLayout>
</template>
