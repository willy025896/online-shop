<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import StatCardSkeleton from '@/Components/Dashboard/StatCardSkeleton.vue';
import OrderStatusGrid from '@/Components/Dashboard/OrderStatusGrid.vue';
import PeriodTabs from '@/Components/Dashboard/PeriodTabs.vue';
import RevenueLineChart from '@/Components/Charts/RevenueLineChart.vue';
import Skeleton from '@/Components/Skeleton.vue';
import TableSkeletonRows from '@/Components/TableSkeletonRows.vue';

defineProps({
    period: String,
    stats: Object,
    chartData: Array,
    topShops: Array,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
const isLoading = ref(false);

const setPeriod = (p) => {
    router.get(route('admin.dashboard'), { period: p }, {
        preserveState: true,
        preserveScroll: true,
        only: ['stats', 'chartData', 'topShops', 'period'],
        onStart: () => { isLoading.value = true; },
        onFinish: () => { isLoading.value = false; },
    });
};

const formatCurrency = (v) => `$${Number(v ?? 0).toFixed(2)}`;
</script>

<template>
    <AdminLayout :title="lang.dashboard">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.dashboard }}</h2>
        </template>

        <!-- All-time platform totals -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <StatCard :label="lang.total_users" :value="stats.total_users" />
            <StatCard :label="lang.total_shops" :value="stats.total_shops" />
            <StatCard :label="lang.pending_shops" :value="stats.pending_shops" color="yellow" />
            <StatCard :label="lang.total_products" :value="stats.total_products" />
            <StatCard :label="lang.total_orders" :value="stats.total_orders" />
            <StatCard :label="lang.total_revenue" :value="formatCurrency(stats.total_revenue)" color="green" />
        </div>

        <!-- Period-scoped analytics -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200">{{ lang.analytics }}</h3>
            <PeriodTabs :period="period" :lang="lang" @change="setPeriod" />
        </div>

        <div v-if="isLoading" role="status" aria-busy="true" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <span class="sr-only">{{ lang.loading }}</span>
            <StatCardSkeleton with-growth />
            <StatCardSkeleton />
        </div>
        <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard
                :label="lang.revenue"
                :value="formatCurrency(stats.revenue)"
                :growth="stats.revenue_growth"
                color="green"
            />
            <StatCard :label="lang.period_orders" :value="stats.period_orders" />
        </div>

        <!-- Chart + status grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ lang.sales_trend }}</p>
                    <div v-if="isLoading" role="status" aria-busy="true" class="absolute inset-0 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                        <span class="sr-only">{{ lang.loading }}</span>
                        <Skeleton height="100%" width="100%" rounded="rounded-lg" />
                    </div>
                    <RevenueLineChart :data="chartData" :label="lang.revenue" />
                </div>
            </div>
            <div>
                <div v-if="isLoading" role="status" aria-busy="true">
                    <span class="sr-only">{{ lang.loading }}</span>
                    <Skeleton height="12rem" rounded="rounded-lg" />
                </div>
                <OrderStatusGrid v-else :counts="stats.order_counts" :lang="lang" />
            </div>
        </div>

        <!-- Top shops -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ lang.top_shops }}</p>
            <div v-if="isLoading" role="status" aria-busy="true">
                <span class="sr-only">{{ lang.loading }}</span>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        <TableSkeletonRows :columns="4" :rows="5" />
                    </tbody>
                </table>
            </div>
            <div v-else-if="topShops.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                {{ lang.top_shops_empty }}
            </div>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700">
                        <th class="pb-2 text-left font-medium">#</th>
                        <th class="pb-2 text-left font-medium">{{ lang.shop_name }}</th>
                        <th class="pb-2 text-right font-medium">{{ lang.orders_col }}</th>
                        <th class="pb-2 text-right font-medium">{{ lang.revenue_col }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    <tr v-for="(s, i) in topShops" :key="i" class="text-gray-700 dark:text-gray-300">
                        <td class="py-2 pr-2 text-gray-400 font-medium">{{ i + 1 }}</td>
                        <td class="py-2 truncate max-w-[200px]">{{ s.shop_name }}</td>
                        <td class="py-2 text-right">{{ s.orders }}</td>
                        <td class="py-2 text-right font-medium">{{ formatCurrency(s.revenue) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
