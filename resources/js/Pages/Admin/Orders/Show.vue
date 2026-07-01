<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';

const props = defineProps({
    order: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
const t = computed(() => lang.value.orders || {});

const formatDateTime = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString();
};

const formatMoney = (v) => Number(v).toFixed(2);

const roleLabel = (role) => {
    if (!role) return t.value.system || 'System';
    const key = `role_${role}`;
    return t.value[key] || role;
};

const initiatedByLabel = (initiatedBy) =>
    initiatedBy === 'seller' ? (t.value.seller || 'Seller') : (t.value.buyer || 'Buyer');

const cancellationStatusLabel = (status) =>
    (t.value.cancellation_statuses && t.value.cancellation_statuses[status]) || status;
</script>

<template>
    <AdminLayout :title="t.details">
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.orders.index')"
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        &larr; {{ t.back_to_list }}
                    </Link>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ order.order_number }}</h2>
                </div>
                <OrderStatusBadge :status="order.status" />
            </div>
        </template>

        <div class="max-w-5xl space-y-6">
            <!-- Order Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ t.details }}</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.customer }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                            {{ order.user?.name }}
                            <span class="text-gray-400">&lt;{{ order.user?.email }}&gt;</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.shop }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shop?.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.date }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ formatDateTime(order.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.total }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">${{ formatMoney(order.total) }}</dd>
                    </div>
                    <div v-if="order.notes" class="col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.notes }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ order.notes }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Shipping Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ t.shipping_to }}</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.name }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.phone }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_phone }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.address }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_address }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Order Items -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t.order_items }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.product }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.price }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.quantity }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.subtotal }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ item.product?.name || item.product_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${{ formatMoney(item.unit_price) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ item.quantity }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">${{ formatMoney(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-right space-y-1">
                    <p class="text-sm text-gray-500">{{ t.subtotal }}: ${{ formatMoney(order.subtotal) }}</p>
                    <p v-if="Number(order.discount) > 0" class="text-sm text-green-600">{{ t.discount }}<span v-if="order.coupon_code"> ({{ order.coupon_code }})</span>: -${{ formatMoney(order.discount) }}</p>
                    <p class="text-sm text-gray-500">{{ t.shipping }}: ${{ formatMoney(order.shipping_fee) }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ t.total }}: ${{ formatMoney(order.total) }}</p>
                </div>
            </div>

            <!-- Cancellation Records -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t.cancellations }}</h3>
                </div>
                <table v-if="order.cancellations && order.cancellations.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.when }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.initiated_by }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.status }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.reason }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.responder }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.response_reason }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="c in order.cancellations" :key="c.id">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDateTime(c.created_at) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ initiatedByLabel(c.initiated_by) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ cancellationStatusLabel(c.status) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ c.reason }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ c.responder?.name || '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ c.response_reason || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="px-6 py-8 text-sm text-center text-gray-500">{{ t.no_cancellations }}</p>
            </div>

            <!-- Status Change Log -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t.status_log }}</h3>
                </div>
                <table v-if="order.status_logs && order.status_logs.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.when }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.from }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.to }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.changed_by }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="log in order.status_logs" :key="log.id">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDateTime(log.created_at) }}</td>
                            <td class="px-6 py-4">
                                <OrderStatusBadge v-if="log.from_status" :status="log.from_status" />
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-6 py-4"><OrderStatusBadge :status="log.to_status" /></td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <template v-if="log.changed_by">
                                    {{ log.changed_by.name }}
                                    <span class="text-gray-400 text-xs">({{ roleLabel(log.changed_by.role) }})</span>
                                </template>
                                <span v-else class="text-gray-400">{{ t.system }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="px-6 py-8 text-sm text-center text-gray-500">{{ t.no_status_log }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
