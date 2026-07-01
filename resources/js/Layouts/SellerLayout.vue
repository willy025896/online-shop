<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';

defineProps({
    title: String,
});

const page = usePage();
const nav = computed(() => page.props.nav || {});
const locale = computed(() => page.props.locale);

const sidebarOpen = ref(false);

const logout = () => {
    router.post(route('logout'));
};

const switchLocale = (loc) => {
    router.post(route('locale.store'), { locale: loc }, { preserveScroll: true });
};

const navItems = computed(() => [
    { key: 'seller_dashboard',     route: 'seller.dashboard',       icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1' },
    { key: 'seller_products',      route: 'seller.products.index',   icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { key: 'seller_orders',        route: 'seller.orders.index',     icon: 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
    { key: 'seller_coupons',       route: 'seller.coupons.index',    icon: 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 8V3a2 2 0 012-2z' },
    { key: 'seller_reviews',        route: 'seller.reviews.index',    icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
    { key: 'seller_shop_settings', route: 'seller.shop.edit',        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
]);
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <!-- Mobile sidebar toggle -->
            <div class="lg:hidden flex items-center justify-between bg-white dark:bg-gray-800 border-b px-4 py-3">
                <Link :href="route('seller.dashboard')">
                    <ApplicationMark class="block h-9 w-auto" />
                </Link>
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <div class="flex">
                <!-- Sidebar -->
                <aside :class="sidebarOpen ? 'block' : 'hidden'" class="lg:block w-64 min-h-screen bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                    <div class="hidden lg:flex items-center h-16 px-6 border-b border-gray-200 dark:border-gray-700">
                        <Link :href="route('seller.dashboard')">
                            <ApplicationMark class="block h-9 w-auto" />
                        </Link>
                        <span class="ml-3 font-semibold text-gray-700 dark:text-gray-200">{{ nav.seller_title }}</span>
                    </div>

                    <nav class="mt-4 px-3 space-y-1">
                        <Link
                            v-for="item in navItems"
                            :key="item.route"
                            :href="route(item.route)"
                            :class="[
                                route().current(item.route + '*')
                                    ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                                'flex items-center px-3 py-2 rounded-md text-sm font-medium'
                            ]"
                        >
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            {{ nav[item.key] }}
                        </Link>
                    </nav>

                    <div class="absolute bottom-0 w-64 p-4 border-t border-gray-200 dark:border-gray-700">
                        <!-- Locale switcher -->
                        <div class="flex gap-2 text-xs font-medium mb-3">
                            <button @click="switchLocale('en')"
                                :class="locale === 'en' ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                                EN
                            </button>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <button @click="switchLocale('zh_TW')"
                                :class="locale === 'zh_TW' ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                                中文
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="flex items-center gap-1">
                                <NotificationBell />
                                <Dropdown align="right" width="48" position="top">
                                    <template #trigger>
                                        <button class="text-gray-400 hover:text-gray-600">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('home')">{{ nav.back_to_store }}</DropdownLink>
                                        <DropdownLink :href="route('profile.show')">{{ nav.profile }}</DropdownLink>
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">{{ nav.log_out }}</DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 p-6 lg:p-8">
                    <header v-if="$slots.header" class="mb-6">
                        <slot name="header" />
                    </header>

                    <!-- Flash messages -->
                    <div v-if="$page.props.flash?.success" class="mb-4 rounded-md bg-green-50 p-4">
                        <p class="text-sm text-green-800">{{ $page.props.flash.success }}</p>
                    </div>
                    <div v-if="$page.props.flash?.error" class="mb-4 rounded-md bg-red-50 p-4">
                        <p class="text-sm text-red-800">{{ $page.props.flash.error }}</p>
                    </div>

                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
