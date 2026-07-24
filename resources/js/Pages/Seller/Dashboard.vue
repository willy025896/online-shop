<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import StatCardSkeleton from '@/Components/Dashboard/StatCardSkeleton.vue';
import OrderStatusGrid from '@/Components/Dashboard/OrderStatusGrid.vue';
import PeriodTabs from '@/Components/Dashboard/PeriodTabs.vue';
import TopProductsTable from '@/Components/Dashboard/TopProductsTable.vue';
import LowStockAlert from '@/Components/Dashboard/LowStockAlert.vue';
import WidgetSettings from '@/Components/Dashboard/WidgetSettings.vue';
import RevenueLineChart from '@/Components/Charts/RevenueLineChart.vue';
import Skeleton from '@/Components/Skeleton.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    shop: Object,
    period: String,
    stats: Object,
    chartData: Array,
    topProducts: Array,
    lowStockProducts: Array,
    recentOrders: Array,
    userPreferences: Object,
    widgets: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
const isLoading = ref(false);
const toast = useToast();

const localWidgets = ref({ ...props.widgets });

watch(() => props.widgets, (v) => {
    localWidgets.value = { ...v };
}, { deep: true });

const setPeriod = (p) => {
    router.get(route('seller.dashboard'), { period: p }, {
        preserveState: true,
        only: ['stats', 'chartData', 'topProducts', 'period'],
        onStart: () => { isLoading.value = true; },
        onFinish: () => { isLoading.value = false; },
    });
};

const savePreferences = (updated) => {
    const previous = { ...localWidgets.value };
    localWidgets.value = updated;
    router.patch(route('seller.preferences.update'), {
        dashboard_widgets: updated,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            localWidgets.value = previous;
            toast.error(errors.dashboard_widgets);
        },
    });
};

const formatCurrency = (v) => `$${Number(v ?? 0).toFixed(2)}`;
</script>

<template>
    <SellerLayout :title="lang.dashboard">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.dashboard }}</h2>
        </template>

        <!-- Shop status warning -->
        <div v-if="shop.status === 'pending'" class="mb-6 rounded-md bg-yellow-50 border border-yellow-200 p-4">
            <p class="text-sm text-yellow-800">{{ lang.shop_pending }}</p>
        </div>

        <!-- Period tabs -->
        <PeriodTabs :period="period" :lang="lang" class="mb-6" @change="setPeriod" />

        <!-- Stat cards row -->
        <div v-if="isLoading" role="status" aria-busy="true" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <span class="sr-only">載入中…</span>
            <StatCardSkeleton v-if="localWidgets.revenue" with-growth />
            <StatCardSkeleton />
            <StatCardSkeleton />
            <StatCardSkeleton v-if="localWidgets.order_status" />
        </div>
        <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard
                v-if="localWidgets.revenue"
                :label="lang.revenue"
                :value="formatCurrency(stats.revenue)"
                :growth="stats.revenue_growth"
            />
            <StatCard
                :label="lang.total_products"
                :value="stats.total_products"
            />
            <StatCard
                :label="lang.active_products"
                :value="stats.active_products"
                color="green"
            />
            <StatCard
                v-if="localWidgets.order_status"
                :label="lang.total_orders"
                :value="stats.total_orders"
            />
        </div>

        <!-- Chart + status grid row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2">
                <div v-if="localWidgets.revenue_chart" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ lang.sales_trend }}</p>
                    <Skeleton v-if="isLoading" role="status" aria-busy="true" aria-label="載入中" height="16rem" rounded="rounded-md" />
                    <RevenueLineChart v-else :data="chartData" :label="lang.revenue" />
                </div>
            </div>
            <div v-if="localWidgets.order_status">
                <Skeleton v-if="isLoading" role="status" aria-busy="true" aria-label="載入中" height="12rem" rounded="rounded-lg" />
                <OrderStatusGrid v-else :counts="stats.order_counts" :lang="lang" />
            </div>
        </div>

        <!-- Top products + recent orders row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <TopProductsTable
                v-if="localWidgets.top_products"
                :products="topProducts"
                :lang="lang"
            />

            <!-- Recent Orders -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ lang.recent_orders }}</h3>
                    <Link :href="route('seller.orders.index')" class="text-xs text-brand-500 hover:text-brand-700">{{ lang.view_all }}</Link>
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
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(order.total) }}</span>
                            <OrderStatusBadge :status="order.status" />
                        </div>
                    </Link>
                </div>
                <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                    {{ lang.no_orders }}
                </div>
            </div>
        </div>

        <!-- Low stock alert row -->
        <div v-if="localWidgets.low_stock" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <LowStockAlert
                :products="lowStockProducts"
                :count="stats.low_stock_count"
                :lang="lang"
            />
        </div>

        <!-- Widget settings FAB -->
        <WidgetSettings
            :widgets="localWidgets"
            :lang="lang"
            @save="savePreferences"
        />
    </SellerLayout>
</template>
