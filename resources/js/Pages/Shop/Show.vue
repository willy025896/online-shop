<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    shop: Object,
    products: Object,
});
</script>

<template>
    <AppLayout :title="shop.name">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6 mb-8">
                <div class="h-20 w-20 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
                    <img v-if="shop.logo_path" :src="`/storage/${shop.logo_path}`" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-3xl font-bold text-gray-400">
                        {{ shop.name[0] }}
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ shop.name }}</h1>
                    <p v-if="shop.description" class="mt-1 text-gray-600 dark:text-gray-400">{{ shop.description }}</p>
                </div>
            </div>

            <div v-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
            </div>
            <div v-else class="text-center py-12 text-gray-500">
                No products yet.
            </div>

            <div class="mt-8">
                <Pagination :links="products.links" />
            </div>
        </div>
    </AppLayout>
</template>
