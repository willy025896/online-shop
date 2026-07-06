<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';

const props = defineProps({
    order: Object,
    shopName: String,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
const userRole = computed(() => page.props.userRole);

const orderHref = computed(() =>
    userRole.value === 'seller' || userRole.value === 'admin'
        ? route('seller.orders.show', props.order.id)
        : route('orders.show', props.order.id)
);
</script>

<template>
    <div class="bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-100 dark:border-indigo-900/40 px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">
                        {{ order.order_number }}
                    </span>
                    <OrderStatusBadge :status="order.status" />
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                    {{ shopName }} · ${{ Number(order.total).toFixed(2) }}
                </p>
            </div>
            <Link
                :href="orderHref"
                class="shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
            >
                {{ lang.view_order || 'View order' }} →
            </Link>
        </div>
    </div>
</template>
