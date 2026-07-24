<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import TableSkeletonRows from '@/Components/TableSkeletonRows.vue';
import { useAsyncAction } from '@/Composables/useAsyncAction';
import { useInFlightLoading } from '@/Composables/useInFlightLoading';
import { useToast } from '@/Composables/useToast';
import { skeletonRowCount } from '@/Utils/skeletonRowCount';

const props = defineProps({
    payouts: Object,
    pendingPreview: Array,
});

const page = usePage();
const t = computed(() => page.props.lang?.payouts || {});
const toast = useToast();
const { isLoading, start: startLoading, finish: finishLoading } = useInFlightLoading();
const skeletonRows = computed(() => skeletonRowCount(props.payouts));

const { processing: running, run } = useAsyncAction();

const runPayouts = () => {
    run((finish) => router.post(route('admin.payouts.run'), {}, {
        preserveScroll: true,
        onError: (errors) => toast.error(errors.error),
        onFinish: finish,
    }));
};

const formatDateTime = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString();
};
</script>

<template>
    <AdminLayout :title="t.title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ t.title }}</h2>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t.pending_preview }}</h3>
                <button
                    :disabled="running"
                    :class="{ 'opacity-50': running }"
                    @click="runPayouts"
                    class="bg-brand-500 text-white py-2 px-4 rounded-lg hover:bg-brand-600 transition text-sm font-medium"
                >
                    {{ running ? t.running : t.run }}
                </button>
            </div>

            <table v-if="pendingPreview.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.shop }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.order_count }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.estimated_net }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="row in pendingPreview" :key="row.shop_id">
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ row.shop_name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ row.order_count }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${{ Number(row.net_amount).toFixed(2) }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">{{ t.no_pending }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-x-auto">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 p-6 pb-0">{{ t.history }}</h3>

            <table v-if="isLoading || payouts.data.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 mt-4">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.paid_at }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.shop }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.gross_amount }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.commission_amount }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.shipping_amount }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.net_amount }}</th>
                    </tr>
                </thead>
                <tbody v-if="isLoading" role="status" aria-busy="true" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <TableSkeletonRows :columns="6" :rows="skeletonRows" />
                </tbody>
                <tbody v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="payout in payouts.data" :key="payout.id">
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(payout.paid_at) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ payout.shop?.name }}</td>
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
            <Pagination :links="payouts.links" @start="startLoading" @finish="finishLoading" />
        </div>
    </AdminLayout>
</template>
