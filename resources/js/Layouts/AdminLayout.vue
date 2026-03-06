<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    title: String,
});

const sidebarOpen = ref(false);

const logout = () => {
    router.post(route('logout'));
};

const navItems = [
    { name: 'Dashboard', route: 'admin.dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1' },
    { name: 'Users', route: 'admin.users.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Shops', route: 'admin.shops.index', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { name: 'Categories', route: 'admin.categories.index', icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' },
    { name: 'Products', route: 'admin.products.index', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { name: 'Orders', route: 'admin.orders.index', icon: 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
];
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <!-- Mobile sidebar toggle -->
            <div class="lg:hidden flex items-center justify-between bg-white dark:bg-gray-800 border-b px-4 py-3">
                <Link :href="route('admin.dashboard')">
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
                        <Link :href="route('admin.dashboard')">
                            <ApplicationMark class="block h-9 w-auto" />
                        </Link>
                        <span class="ml-3 font-semibold text-gray-700 dark:text-gray-200">Admin Panel</span>
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
                            {{ item.name }}
                        </Link>
                    </nav>

                    <div class="absolute bottom-0 w-64 p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <Dropdown align="right" width="48" position="top">
                                <template #trigger>
                                    <button class="text-gray-400 hover:text-gray-600">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('home')">Back to Store</DropdownLink>
                                    <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                                    <form @submit.prevent="logout">
                                        <DropdownLink as="button">Log Out</DropdownLink>
                                    </form>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="flex-1 p-6 lg:p-8">
                    <header v-if="$slots.header" class="mb-6">
                        <slot name="header" />
                    </header>

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
