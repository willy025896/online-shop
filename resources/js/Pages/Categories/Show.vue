<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import ProductCardSkeleton from '@/Components/ProductCardSkeleton.vue';
import Pagination from '@/Components/Pagination.vue';
import MinRatingFilter from '@/Components/MinRatingFilter.vue';
import { useListingFilters } from '@/Composables/useListingFilters';

const props = defineProps({
    category: Object,
    products: Object,
    filters: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const {
    isLoading, startLoading, finishLoading,
    localMinPrice, localMaxPrice, hasPriceFilter,
    updateFilters, applyPriceFilter, clearPriceFilter,
} = useListingFilters({
    routeName: 'categories.show',
    routeParams: () => props.category.slug,
    getFilters: () => props.filters,
});
</script>

<template>
    <AppLayout :title="category.name">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <Link :href="route('products.index')" class="hover:text-brand-500">{{ lang.products }}</Link>
                    <span>/</span>
                    <Link v-if="category.parent" :href="route('categories.show', category.parent.slug)" class="hover:text-brand-500">
                        {{ category.parent.name }}
                    </Link>
                    <span v-if="category.parent">/</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ category.name }}</span>
                </div>
                <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ category.name }}</h1>
            </div>

            <div v-if="category.children?.length" class="flex flex-wrap gap-2 mb-6">
                <Link
                    v-for="child in category.children"
                    :key="child.id"
                    :href="route('categories.show', child.slug)"
                    class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm text-gray-700 dark:text-gray-300 hover:bg-brand-100 hover:text-brand-700 transition"
                >
                    {{ child.name }}
                </Link>
            </div>

            <div class="flex flex-wrap items-center gap-3 mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 flex-1">
                    {{ (lang.found || ':count product(s) found').replace(':count', products.total) }}
                </p>

                <MinRatingFilter
                    :model-value="filters?.min_rating"
                    @update:model-value="value => updateFilters({ min_rating: value ?? undefined })"
                />

                <!-- Price Range -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="localMinPrice"
                        type="number"
                        min="0"
                        :placeholder="lang.min_price"
                        class="w-20 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-brand-400 focus:ring-accent-400"
                        @keyup.enter="applyPriceFilter"
                    />
                    <span class="text-gray-400 flex-shrink-0">–</span>
                    <input
                        v-model="localMaxPrice"
                        type="number"
                        min="0"
                        :placeholder="lang.max_price"
                        class="w-20 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-brand-400 focus:ring-accent-400"
                        @keyup.enter="applyPriceFilter"
                    />
                    <button
                        @click="applyPriceFilter"
                        class="px-3 py-1.5 text-xs font-medium rounded-md bg-brand-500 text-white hover:bg-brand-600 transition"
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

                <select
                    :value="filters?.sort || 'latest'"
                    @change="updateFilters({ sort: $event.target.value })"
                    class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-brand-400 focus:ring-accent-400"
                >
                    <option value="latest">{{ lang.sort?.latest }}</option>
                    <option value="price_asc">{{ lang.sort?.price_asc }}</option>
                    <option value="price_desc">{{ lang.sort?.price_desc }}</option>
                    <option value="rating_desc">{{ lang.sort?.rating_desc }}</option>
                    <option value="name">{{ lang.sort?.name }}</option>
                </select>
            </div>

            <div v-if="isLoading" role="status" aria-busy="true" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <span class="sr-only">{{ lang.loading }}</span>
                <ProductCardSkeleton v-for="n in 8" :key="n" />
            </div>
            <div v-else-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
            </div>
            <div v-else class="text-center py-12 text-gray-500">
                {{ lang.no_products }}
            </div>

            <div class="mt-8">
                <Pagination :links="products.links" @start="startLoading" @finish="finishLoading" />
            </div>
        </div>
    </AppLayout>
</template>
