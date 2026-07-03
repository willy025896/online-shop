<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import ProductCardSkeleton from '@/Components/ProductCardSkeleton.vue';
import ImageWithFallback from '@/Components/ImageWithFallback.vue';
import Pagination from '@/Components/Pagination.vue';
import StarRating from '@/Components/StarRating.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    shop: Object,
    products: Object,
    categories: Array,
    filters: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const search = ref(props.filters?.search ?? '');
const selectedCategory = ref(props.filters?.category ?? '');
const sort = ref(props.filters?.sort ?? 'latest');
const localMinPrice = ref(props.filters?.min_price ?? '');
const localMaxPrice = ref(props.filters?.max_price ?? '');
const hasPriceFilter = computed(() => localMinPrice.value || localMaxPrice.value);
const isLoading = ref(false);
const skeletonCount = computed(() => props.products.data.length || props.products.per_page || 8);

let searchTimer = null;

const applyFilters = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (selectedCategory.value) params.category = selectedCategory.value;
    if (sort.value !== 'latest') params.sort = sort.value;
    if (localMinPrice.value) params.min_price = localMinPrice.value;
    if (localMaxPrice.value) params.max_price = localMaxPrice.value;

    router.get(route('shops.show', props.shop.slug), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['products', 'filters'],
        onStart: () => { isLoading.value = true; },
        onFinish: () => { isLoading.value = false; },
    });
};

const clearPriceFilter = () => {
    localMinPrice.value = '';
    localMaxPrice.value = '';
    applyFilters();
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
                    <ImageWithFallback :src="shop.logo_path ? `/storage/${shop.logo_path}` : null" :alt="shop.name" class="w-full h-full object-cover">
                        <div class="w-full h-full flex items-center justify-center text-3xl font-bold text-gray-400">
                            {{ shop.name[0] }}
                        </div>
                    </ImageWithFallback>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ shop.name }}</h1>
                    <p v-if="shop.description" class="mt-1 text-gray-600 dark:text-gray-400">{{ shop.description }}</p>
                    <div v-if="shop.reviews_count > 0" class="flex items-center gap-2 mt-2">
                        <StarRating :model-value="Math.round(shop.rating_sum / shop.reviews_count)" :readonly="true" size="sm" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ (shop.rating_sum / shop.reviews_count).toFixed(1) }}
                            <span class="text-gray-400">({{ shop.reviews_count }} 則評論)</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Search + Sort bar -->
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <div class="flex flex-1">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="lang.search"
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    />
                </div>
                <select
                    v-model="sort"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                    <option value="latest">{{ lang.sort?.latest }}</option>
                    <option value="price_asc">{{ lang.sort?.price_asc }}</option>
                    <option value="price_desc">{{ lang.sort?.price_desc }}</option>
                    <option value="name">{{ lang.sort?.name }}</option>
                </select>
            </div>

            <!-- Price Range -->
            <div class="flex items-center gap-2 mb-4">
                <span class="text-sm text-gray-600 dark:text-gray-400 flex-shrink-0">{{ lang.price_range }}</span>
                <input
                    v-model="localMinPrice"
                    type="number"
                    min="0"
                    :placeholder="lang.min_price"
                    class="w-24 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                    @keyup.enter="applyFilters"
                />
                <span class="text-gray-400">–</span>
                <input
                    v-model="localMaxPrice"
                    type="number"
                    min="0"
                    :placeholder="lang.max_price"
                    class="w-24 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                    @keyup.enter="applyFilters"
                />
                <button
                    @click="applyFilters"
                    class="px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 transition"
                >
                    {{ lang.apply }}
                </button>
                <button
                    v-if="hasPriceFilter"
                    @click="clearPriceFilter"
                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-600 hover:border-gray-400 dark:border-gray-600 dark:text-gray-400 transition"
                >
                    {{ lang.clear }}
                </button>
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
                    {{ lang.all }}
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
            <div v-if="isLoading" role="status" aria-busy="true" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <span class="sr-only">{{ lang.loading }}</span>
                <ProductCardSkeleton v-for="n in skeletonCount" :key="n" />
            </div>
            <div v-else-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
            </div>
            <div v-else class="text-center py-12 text-gray-500">
                {{ lang.no_products }}
            </div>

            <div class="mt-8">
                <Pagination :links="products.links" @start="isLoading = true" @finish="isLoading = false" />
            </div>
        </div>
    </AppLayout>
</template>
