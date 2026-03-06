<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    shops: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const updateStatus = (shop, status) => {
    router.patch(route('admin.shops.status', shop.id), { status });
};

const statusClass = (status) => {
    const map = {
        approved: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        suspended: 'bg-red-100 text-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AdminLayout :title="lang.shops?.title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.shops?.title }}</h2>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.shops?.shop }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.shops?.owner }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.shops?.status }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.shops?.created }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.shops?.actions }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="shop in shops.data" :key="shop.id">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ shop.name }}</p>
                            <p class="text-xs text-gray-500">/shop/{{ shop.slug }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ shop.user?.name }}</td>
                        <td class="px-6 py-4">
                            <span :class="[statusClass(shop.status), 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium']">
                                {{ lang.shops?.[shop.status] || shop.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(shop.created_at).toLocaleDateString() }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <button v-if="shop.status !== 'approved'" @click="updateStatus(shop, 'approved')" class="text-green-600 hover:text-green-900">{{ lang.shops?.approve }}</button>
                            <button v-if="shop.status !== 'suspended'" @click="updateStatus(shop, 'suspended')" class="text-red-600 hover:text-red-900">{{ lang.shops?.suspend }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <Pagination :links="shops.links" />
        </div>
    </AdminLayout>
</template>
