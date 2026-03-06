<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    products: Object,
});
</script>

<template>
    <AdminLayout title="Products">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Products</h2>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table v-if="products.data.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Shop</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
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
                                    <Link :href="route('products.show', product.slug)" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600">
                                        {{ product.name }}
                                    </Link>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ product.shop?.name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ product.category?.name || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">${{ product.price }}</td>
                        <td class="px-6 py-4 text-sm" :class="product.stock === 0 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100'">{{ product.stock }}</td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                product.status === 'active' ? 'bg-green-100 text-green-800' :
                                product.status === 'draft' ? 'bg-gray-100 text-gray-800' :
                                'bg-red-100 text-red-800'
                            ]">{{ product.status }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-gray-500">
                No products yet.
            </div>
        </div>

        <div class="mt-6">
            <Pagination :links="products.links" />
        </div>
    </AdminLayout>
</template>