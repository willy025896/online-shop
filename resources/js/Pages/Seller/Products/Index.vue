<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    products: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const deleteProduct = (product) => {
    const msg = (lang.value.products?.delete_confirm || 'Are you sure you want to delete ":name"?').replace(':name', product.name);
    if (confirm(msg)) {
        router.delete(route('seller.products.destroy', product.id));
    }
};
</script>

<template>
    <SellerLayout :title="lang.products?.title">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.products?.title }}</h2>
                <Link :href="route('seller.products.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    {{ lang.products?.add }}
                </Link>
            </div>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table v-if="products.data.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.name }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.category }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.price }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.stock }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.status }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.actions }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="product in products.data" :key="product.id">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 rounded bg-gray-200 dark:bg-gray-600 overflow-hidden">
                                    <img v-if="product.primary_image" :src="`/storage/${product.primary_image.path}`" class="h-full w-full object-cover" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ product.name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ product.category?.name || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">${{ product.price }}</td>
                        <td class="px-6 py-4 text-sm" :class="product.stock === 0 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100'">{{ product.stock }}</td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                product.status === 'active' ? 'bg-green-100 text-green-800' :
                                product.status === 'draft' ? 'bg-gray-100 text-gray-800' :
                                'bg-red-100 text-red-800'
                            ]">{{ lang.products?.[product.status] || product.status }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <Link :href="route('seller.products.edit', product.id)" class="text-indigo-600 hover:text-indigo-900">{{ lang.products?.action_edit }}</Link>
                            <button @click="deleteProduct(product)" class="text-red-600 hover:text-red-900">{{ lang.products?.action_delete }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-gray-500">
                {{ lang.products?.no_products }} <Link :href="route('seller.products.create')" class="text-indigo-600 hover:underline">{{ lang.products?.create_first }}</Link>.
            </div>
        </div>

        <div class="mt-6">
            <Pagination :links="products.links" />
        </div>
    </SellerLayout>
</template>
