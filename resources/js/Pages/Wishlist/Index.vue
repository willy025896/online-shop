<script setup>
import { computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    products: Array,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const removeFromWishlist = (productId) => {
    router.delete(route('wishlist.destroy', productId), {
        preserveScroll: true,
    });
};

const addToCart = (productId) => {
    router.post(
        route('cart.store'),
        { product_id: productId, quantity: 1 },
        { preserveScroll: true }
    );
};
</script>

<template>
    <AppLayout :title="lang.title || 'My Wishlist'">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                {{ lang.title || 'My Wishlist' }}
            </h1>

            <div v-if="products.length === 0" class="text-center py-20">
                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ lang.empty || 'Your wishlist is empty.' }}</p>
                <Link :href="route('products.index')" class="inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                    {{ lang.browse_products || 'Browse Products' }}
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden flex flex-col"
                >
                    <Link :href="route('products.show', product.slug)" class="block aspect-square bg-gray-200 dark:bg-gray-700 overflow-hidden">
                        <img
                            v-if="product.primary_image"
                            :src="`/storage/${product.primary_image.path}`"
                            :alt="product.name"
                            class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </Link>

                    <div class="p-4 flex flex-col flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ product.shop?.name }}</p>
                        <Link :href="route('products.show', product.slug)" class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2 mb-2 hover:text-indigo-600">
                            {{ product.name }}
                        </Link>
                        <span class="text-lg font-bold text-red-600 mb-4">${{ product.price }}</span>

                        <div class="mt-auto flex gap-2">
                            <button
                                v-if="product.stock > 0"
                                @click="addToCart(product.id)"
                                class="flex-1 text-sm bg-indigo-600 text-white py-2 px-3 rounded-lg hover:bg-indigo-700 transition font-medium"
                            >
                                {{ lang.add_to_cart || 'Add to Cart' }}
                            </button>
                            <span v-else class="flex-1 text-sm text-center py-2 text-red-500 font-medium">
                                {{ lang.out_of_stock || 'Out of stock' }}
                            </span>

                            <button
                                @click="removeFromWishlist(product.id)"
                                class="p-2 text-gray-400 hover:text-red-500 transition rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                :title="lang.remove || 'Remove'"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
