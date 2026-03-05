<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import SearchBar from '@/Components/SearchBar.vue';
import CategoryTree from '@/Components/CategoryTree.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});
</script>

<template>
    <AppLayout title="Products">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <SearchBar :model-value="filters?.search || ''" />
            </div>

            <div class="flex gap-8">
                <!-- Sidebar categories -->
                <aside class="hidden lg:block w-56 flex-shrink-0">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Categories</h3>
                    <CategoryTree :categories="categories" />
                </aside>

                <!-- Products grid -->
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ products.total }} product(s) found
                        </p>
                        <select
                            @change="$inertia.get(route('products.index'), { ...filters, sort: $event.target.value }, { preserveState: true })"
                            class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="">Latest</option>
                            <option value="price_asc" :selected="filters?.sort === 'price_asc'">Price: Low to High</option>
                            <option value="price_desc" :selected="filters?.sort === 'price_desc'">Price: High to Low</option>
                        </select>
                    </div>

                    <div v-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
                    </div>
                    <div v-else class="text-center py-12 text-gray-500">
                        No products found.
                    </div>

                    <div class="mt-8">
                        <Pagination :links="products.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
