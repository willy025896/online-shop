<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';

defineProps({
    order: Object,
});

const pay = (orderId) => {
    router.post(route('orders.pay', orderId));
};

const cancel = (orderId) => {
    if (confirm('Are you sure you want to cancel this order?')) {
        router.post(route('orders.cancel', orderId));
    }
};
</script>

<template>
    <AppLayout :title="`Order ${order.order_number}`">
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ order.order_number }}</h1>
                        <p class="text-sm text-gray-500">{{ new Date(order.created_at).toLocaleString() }}</p>
                    </div>
                    <OrderStatusBadge :status="order.status" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Shop</h3>
                        <p class="text-gray-900 dark:text-gray-100">{{ order.shop?.name }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Shipping To</h3>
                        <p class="text-gray-900 dark:text-gray-100">{{ order.shipping_name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.shipping_phone }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.shipping_address }}</p>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-4">Order Items</h3>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4 py-3">
                            <div class="h-16 w-16 bg-gray-200 dark:bg-gray-700 rounded-md overflow-hidden flex-shrink-0">
                                <img v-if="item.product_image" :src="`/storage/${item.product_image}`" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.product_name }}</p>
                                <p class="text-xs text-gray-500">${{ item.unit_price }} x {{ item.quantity }}</p>
                            </div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ Number(item.subtotal).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>Subtotal</span>
                        <span>${{ Number(order.subtotal).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>Shipping</span>
                        <span>{{ order.shipping_fee > 0 ? `$${Number(order.shipping_fee).toFixed(2)}` : 'Free' }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-gray-100">
                        <span>Total</span>
                        <span>${{ Number(order.total).toFixed(2) }}</span>
                    </div>
                </div>

                <div v-if="order.notes" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Notes</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.notes }}</p>
                </div>

                <div class="flex gap-3 mt-6">
                    <button
                        v-if="order.status === 'pending' && !order.paid_at"
                        @click="pay(order.id)"
                        class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 transition text-sm font-medium"
                    >
                        Pay Now (Simulated)
                    </button>
                    <button
                        v-if="order.status === 'pending'"
                        @click="cancel(order.id)"
                        class="bg-red-600 text-white py-2 px-6 rounded-lg hover:bg-red-700 transition text-sm font-medium"
                    >
                        Cancel Order
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
