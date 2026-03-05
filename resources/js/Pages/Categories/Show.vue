<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    category: Object,
    products: Object,
});
</script>

<template>
    <AppLayout :title="category.name">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <Link :href="route('products.index')" class="hover:text-indigo-600">Products</Link>
                    <span>/</span>
                    <Link v-if="category.parent" :href="route('categories.show', category.parent.slug)" class="hover:text-indigo-600">
                        {{ category.parent.name }}
                    </Link>
                    <span v-if="category.parent">/</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ category.name }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ category.name }}</h1>
            </div>

            <div v-if="category.children?.length" class="flex flex-wrap gap-2 mb-6">
                <Link
                    v-for="child in category.children"
                    :key="child.id"
                    :href="route('categories.show', child.slug)"
                    class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-100 hover:text-indigo-700 transition"
                >
                    {{ child.name }}
                </Link>
            </div>

            <div v-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
            </div>
            <div v-else class="text-center py-12 text-gray-500">
                No products in this category.
            </div>

            <div class="mt-8">
                <Pagination :links="products.links" />
            </div>
        </div>
    </AppLayout>
</template>
