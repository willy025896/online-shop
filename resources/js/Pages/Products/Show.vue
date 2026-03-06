<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductImageGallery from '@/Components/ProductImageGallery.vue';
import ProductCard from '@/Components/ProductCard.vue';

const props = defineProps({
    product: Object,
    relatedProducts: Array,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const quantity = ref(1);

const addToCart = () => {
    router.post(route('cart.store'), {
        product_id: props.product.id,
        quantity: quantity.value,
    });
};
</script>

<template>
    <AppLayout :title="product.name">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <ProductImageGallery :images="product.images" />

                <div>
                    <Link v-if="product.category" :href="route('categories.show', product.category.slug)" class="text-sm text-indigo-600 hover:text-indigo-800">
                        {{ product.category.name }}
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ product.name }}</h1>

                    <div class="mt-4 flex items-center gap-3">
                        <span class="text-3xl font-bold text-red-600">${{ product.price }}</span>
                        <span v-if="product.compare_price" class="text-lg text-gray-400 line-through">${{ product.compare_price }}</span>
                    </div>

                    <div class="mt-4">
                        <Link :href="route('shops.show', product.shop.slug)" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600">
                            {{ (lang.sold_by || 'Sold by :name').replace(':name', product.shop.name) }}
                        </Link>
                    </div>

                    <div class="mt-6" v-if="product.stock > 0">
                        <p class="text-sm text-green-600 mb-3">{{ (lang.in_stock_count || ':count available').replace(':count', product.stock) }}</p>
                        <div class="flex items-center gap-4">
                            <select v-model="quantity" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option v-for="n in Math.min(product.stock, 10)" :key="n" :value="n">{{ n }}</option>
                            </select>
                            <button @click="addToCart" class="flex-1 bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 transition font-medium">
                                {{ lang.add_to_cart }}
                            </button>
                        </div>
                    </div>
                    <div v-else class="mt-6">
                        <p class="text-red-500 font-medium">{{ lang.out_of_stock }}</p>
                    </div>

                    <div v-if="product.description" class="mt-8 prose dark:prose-invert max-w-none">
                        <h3 class="text-lg font-medium">{{ lang.description }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ product.description }}</p>
                    </div>
                </div>
            </div>

            <div v-if="relatedProducts.length" class="mt-16">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ lang.related_products }}</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <ProductCard v-for="p in relatedProducts" :key="p.id" :product="p" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
