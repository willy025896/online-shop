<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    product: Object,
});
</script>

<template>
    <Link :href="route('products.show', product.slug)" class="group block bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition overflow-hidden">
        <div class="aspect-square bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <img
                v-if="product.primary_image"
                :src="`/storage/${product.primary_image.path}`"
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>
        <div class="p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ product.shop?.name }}</p>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2 mb-2">{{ product.name }}</h3>
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-red-600">${{ product.price }}</span>
                <span v-if="product.compare_price" class="text-sm text-gray-400 line-through">${{ product.compare_price }}</span>
            </div>
            <p v-if="product.stock === 0" class="mt-1 text-xs text-red-500">Out of stock</p>
        </div>
    </Link>
</template>
