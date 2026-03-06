<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Pagination from '@/Components/Pagination.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    shop: Object,
    products: Object,
    categories: Array,
    filters: Object,
});

const search = ref(props.filters.search);
const selectedCategory = ref(props.filters.category);
const sort = ref(props.filters.sort);

let searchTimer = null;

const applyFilters = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (selectedCategory.value) params.category = selectedCategory.value;
    if (sort.value !== 'latest') params.sort = sort.value;

    router.get(route('shops.show', props.shop.slug), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch([selectedCategory, sort], applyFilters);
</script>

<template>
    <AppLayout :title="shop.name">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Shop header -->
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

            <!-- Search + Sort bar -->
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <div class="flex flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search products..."
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    />
                </div>
                <select
                    v-model="sort"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                    <option value="latest">Latest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name">Name A–Z</option>
                </select>
            </div>

            <!-- Category filters -->
            <div v-if="categories.length" class="flex flex-wrap gap-2 mb-6">
                <button
                    @click="selectedCategory = ''"
                    :class="[
                        'px-3 py-1 rounded-full text-sm font-medium transition',
                        selectedCategory === ''
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]"
                >
                    All
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategory = String(cat.id)"
                    :class="[
                        'px-3 py-1 rounded-full text-sm font-medium transition',
                        selectedCategory === String(cat.id)
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]"
                >
                    {{ cat.name }}
                </button>
            </div>

            <!-- Product grid -->
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
    </AppLayout>
</template>
