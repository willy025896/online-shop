<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    payouts: Object,
});

const page = usePage();
const t = computed(() => page.props.lang?.payouts || {});

const formatDateTime = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString();
};
</script>

<template>
    <SellerLayout :title="t.title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ t.title }}</h2>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-x-auto">
            <table v-if="payouts.data.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.paid_at }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.orders }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.gross_amount }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.commission_amount }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.shipping_amount }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.net_amount }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="payout in payouts.data" :key="payout.id">
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(payout.paid_at) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ payout.items_count }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-500 dark:text-gray-400">${{ Number(payout.gross_amount).toFixed(2) }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-500 dark:text-gray-400">${{ Number(payout.commission_amount).toFixed(2) }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-500 dark:text-gray-400">${{ Number(payout.shipping_amount).toFixed(2) }}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium text-gray-900 dark:text-gray-100">${{ Number(payout.net_amount).toFixed(2) }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">{{ t.no_payouts }}</p>
        </div>

        <div class="mt-6">
            <Pagination :links="payouts.links" />
        </div>
    </SellerLayout>
</template>
