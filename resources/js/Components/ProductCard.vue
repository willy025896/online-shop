<script setup>
import { Link } from '@inertiajs/vue3';
import FavoriteButton from '@/Components/FavoriteButton.vue';
import ImageWithFallback from '@/Components/ImageWithFallback.vue';

defineProps({
    product: Object,
});
</script>

<template>
    <Link :href="route('products.show', product.slug)" class="group block bg-white dark:bg-gray-800 rounded-2xl shadow-soft hover:shadow-lift hover:-translate-y-1 transition duration-300 overflow-hidden">
        <div class="relative aspect-square bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <ImageWithFallback
                :src="product.primary_image ? `/storage/${product.primary_image.path}` : null"
                :alt="product.name"
                loading="lazy"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 ease-out"
            />
            <div class="absolute top-2 right-2 bg-white dark:bg-gray-800 rounded-full shadow-soft">
                <FavoriteButton :product-id="product.id" />
            </div>
        </div>
        <div class="p-5">
            <p class="text-xs font-medium tracking-wide text-brand-400 dark:text-brand-300 mb-1">{{ product.shop?.name }}</p>
            <h3 class="font-display text-base text-gray-900 dark:text-gray-100 line-clamp-2 mb-2">{{ product.name }}</h3>
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-accent-600 dark:text-accent-400">${{ product.price }}</span>
                <span v-if="product.compare_price" class="text-sm text-gray-400 line-through">${{ product.compare_price }}</span>
            </div>
            <p v-if="product.stock === 0" class="mt-1 text-xs text-red-500">Out of stock</p>
        </div>
    </Link>
</template>
