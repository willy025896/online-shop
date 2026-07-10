<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import ProductCardSkeleton from '@/Components/ProductCardSkeleton.vue';
import SearchBar from '@/Components/SearchBar.vue';
import CategoryTree from '@/Components/CategoryTree.vue';
import Pagination from '@/Components/Pagination.vue';
import MinRatingFilter from '@/Components/MinRatingFilter.vue';
import { useListingFilters } from '@/Composables/useListingFilters';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const {
    isLoading, startLoading, finishLoading,
    localMinPrice, localMaxPrice, hasPriceFilter,
    updateFilters, applyPriceFilter, clearPriceFilter,
} = useListingFilters({ routeName: 'products.index', getFilters: () => props.filters });
</script>

<template>
    <AppLayout :title="lang.title">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <SearchBar :model-value="filters?.search || ''" />
            </div>

            <div class="flex gap-8">
                <!-- Sidebar -->
                <aside class="hidden lg:block w-56 flex-shrink-0">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ lang.categories }}</h3>
                    <CategoryTree :categories="categories" />

                    <!-- Price Range -->
                    <div class="mt-6">
                        <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ lang.price_range }}</h3>
                        <div class="flex items-center gap-2 mb-2">
                            <input
                                v-model="localMinPrice"
                                type="number"
                                min="0"
                                :placeholder="lang.min_price"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                                @keyup.enter="applyPriceFilter"
                            />
                            <span class="text-gray-400 flex-shrink-0">–</span>
                            <input
                                v-model="localMaxPrice"
                                type="number"
                                min="0"
                                :placeholder="lang.max_price"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                                @keyup.enter="applyPriceFilter"
                            />
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="applyPriceFilter"
                                class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 transition"
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
                    </div>
                </aside>

                <!-- Products grid -->
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 flex-1">
                            {{ (lang.found || ':count product(s) found').replace(':count', products.total) }}
                        </p>

                        <MinRatingFilter
                            :model-value="filters?.min_rating"
                            @update:model-value="value => updateFilters({ min_rating: value ?? undefined })"
                        />

                        <select
                            :value="filters?.sort || 'latest'"
                            @change="updateFilters({ sort: $event.target.value })"
                            class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
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
            </div>
        </div>
    </AppLayout>
</template>
