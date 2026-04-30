<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';

defineProps({
    stats: Object,
    recentOrders: Array,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
const user = computed(() => page.props.auth.user);
const userRole = computed(() => page.props.userRole);

const roleBadgeClass = computed(() => {
    const map = {
        admin:    'bg-red-100 text-red-800',
        seller:   'bg-indigo-100 text-indigo-800',
        customer: 'bg-green-100 text-green-800',
    };
    return map[userRole.value] || 'bg-gray-100 text-gray-800';
});
</script>

<template>
    <AppLayout :title="lang.title">
        <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Profile header -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 flex items-center gap-5">
                <img
                    :src="user.profile_photo_url"
                    :alt="user.name"
                    class="w-16 h-16 rounded-full object-cover"
                />
                <div class="flex-1 min-w-0">
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100 truncate">
                        {{ lang.greeting?.replace(':name', user.name) }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
                    <span :class="[roleBadgeClass, 'mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                        {{ lang.role_badge?.[userRole] }}
                    </span>
                </div>
                <Link
                    :href="route('profile.show')"
                    class="shrink-0 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                >
                    {{ lang.edit_profile }}
                </Link>
            </div>

            <!-- Order stats -->
            <div>
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                    {{ lang.stats?.title }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <Link :href="route('orders.index')" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.stats?.total }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ stats.total }}</p>
                    </Link>
                    <Link :href="route('orders.index')" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.stats?.pending }}</p>
                        <p class="text-2xl font-bold text-yellow-500 mt-1">{{ stats.pending }}</p>
                    </Link>
                    <Link :href="route('orders.index')" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.stats?.in_progress }}</p>
                        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ stats.in_progress }}</p>
                    </Link>
                    <Link :href="route('orders.index')" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang.stats?.completed }}</p>
                        <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.completed }}</p>
                    </Link>
                </div>
            </div>

            <!-- Recent orders -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ lang.recent_orders }}</h2>
                    <Link :href="route('orders.index')" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                        {{ lang.view_all }}
                    </Link>
                </div>

                <div v-if="recentOrders.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <Link
                        v-for="order in recentOrders"
                        :key="order.id"
                        :href="route('orders.show', order.id)"
                        class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ order.order_number }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ order.shop?.name }}</p>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 ml-4">
                            <p class="text-xs text-gray-400">{{ new Date(order.created_at).toLocaleDateString() }}</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${{ Number(order.total).toFixed(2) }}</p>
                            <OrderStatusBadge :status="order.status" />
                        </div>
                    </Link>
                </div>

                <div v-else class="px-6 py-10 text-center text-gray-500 text-sm">
                    {{ lang.no_orders }}
                </div>
            </div>

            <!-- Quick links -->
            <div>
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                    {{ lang.quick_links }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <Link
                        :href="route('products.index')"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-5 py-4 text-sm font-medium text-gray-700 dark:text-gray-300 hover:shadow-md hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                    >
                        {{ lang.browse_products }}
                    </Link>
                    <Link
                        :href="route('orders.index')"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-5 py-4 text-sm font-medium text-gray-700 dark:text-gray-300 hover:shadow-md hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                    >
                        {{ lang.my_orders }}
                    </Link>
                    <Link
                        v-if="userRole === 'customer'"
                        :href="route('seller.register')"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-5 py-4 text-sm font-medium text-gray-700 dark:text-gray-300 hover:shadow-md hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                    >
                        {{ lang.become_seller }}
                    </Link>
                    <Link
                        v-if="userRole === 'seller' || userRole === 'admin'"
                        :href="route('seller.dashboard')"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-5 py-4 text-sm font-medium text-gray-700 dark:text-gray-300 hover:shadow-md hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                    >
                        {{ lang.seller_panel }}
                    </Link>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
