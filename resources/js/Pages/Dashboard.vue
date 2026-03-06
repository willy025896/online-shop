<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const lang = computed(() => page.props.lang || {});
const userRole = computed(() => page.props.userRole);
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <AppLayout :title="lang.title">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ lang.title }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Welcome message -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <p class="text-lg text-gray-700 dark:text-gray-300">
                        {{ lang.welcome }}，{{ user?.name }}
                    </p>
                </div>

                <!-- Role-based action cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Buyer: become seller CTA (only if role is buyer) -->
                    <Link v-if="userRole === 'buyer'"
                          :href="route('seller.register')"
                          class="block bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h3 class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">
                            {{ lang.become_seller }}
                        </h3>
                    </Link>

                    <!-- Seller panel link (only if role is seller) -->
                    <Link v-if="userRole === 'seller'"
                          :href="route('seller.dashboard')"
                          class="block bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h3 class="text-lg font-semibold text-green-600 dark:text-green-400">
                            {{ lang.seller_panel }}
                        </h3>
                    </Link>

                    <!-- Admin panel link (only if role is admin) -->
                    <Link v-if="userRole === 'admin'"
                          :href="route('admin.dashboard')"
                          class="block bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">
                            {{ lang.admin_panel }}
                        </h3>
                    </Link>

                    <!-- Orders link (all authenticated users) -->
                    <Link :href="route('orders.index')"
                          class="block bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                            {{ lang.orders }}
                        </h3>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
