<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';

const props = defineProps({
    order: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const updateStatus = (status) => {
    router.patch(route('seller.orders.status', props.order.id), { status });
};

const nextStatuses = {
    paid: 'processing',
    processing: 'shipped',
    shipped: 'completed',
};
</script>

<template>
    <SellerLayout :title="lang.orders?.details">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ order.order_number }}</h2>
                <OrderStatusBadge :status="order.status" />
            </div>
        </template>

        <div class="max-w-4xl space-y-6">
            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ lang.orders?.customer_info }}</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ lang.orders?.name }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.user?.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ lang.orders?.email }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.user?.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ lang.orders?.shipping_name }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ lang.orders?.phone }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_phone }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ lang.orders?.address }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_address }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Order Items -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ lang.orders?.order_items }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.product }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.price }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.qty }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.orders?.subtotal }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ item.product?.name || item.product_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${{ Number(item.price).toFixed(2) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ item.quantity }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">${{ (item.price * item.quantity).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-right space-y-1">
                    <p class="text-sm text-gray-500">{{ lang.orders?.subtotal }}: ${{ Number(order.subtotal).toFixed(2) }}</p>
                    <p class="text-sm text-gray-500">{{ lang.orders?.shipping }}: ${{ Number(order.shipping_fee).toFixed(2) }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ lang.orders?.total }}: ${{ Number(order.total).toFixed(2) }}</p>
                </div>
            </div>

            <!-- Update Status -->
            <div v-if="nextStatuses[order.status]" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ lang.orders?.update_status }}</h3>
                <button
                    @click="updateStatus(nextStatuses[order.status])"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                >
                    {{ lang.orders?.mark_as?.replace(':status', nextStatuses[order.status]) }}
                </button>
            </div>

            <!-- Notes -->
            <div v-if="order.notes" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ lang.orders?.notes }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.notes }}</p>
            </div>
        </div>
    </SellerLayout>
</template>
