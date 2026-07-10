<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import ProductCardSkeleton from '@/Components/ProductCardSkeleton.vue';
import SearchBar from '@/Components/SearchBar.vue';
import CategoryTree from '@/Components/CategoryTree.vue';
import Pagination from '@/Components/Pagination.vue';
import { useInFlightLoading } from '@/Composables/useInFlightLoading';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const { isLoading, start: startLoading, finish: finishLoading } = useInFlightLoading();

const localMinPrice = ref(props.filters?.min_price ?? '');
const localMaxPrice = ref(props.filters?.max_price ?? '');
const hasPriceFilter = computed(() => localMinPrice.value || localMaxPrice.value);

function updateFilters(partial) {
    router.get(route('products.index'), { ...props.filters, ...partial }, {
        preserveState: true,
        only: ['products', 'filters'],
        onStart: startLoading,
        onFinish: finishLoading,
    });
}

function applyPriceFilter() {
    updateFilters({
        min_price: localMinPrice.value || undefined,
        max_price: localMaxPrice.value || undefined,
    });
}

function clearPriceFilter() {
    localMinPrice.value = '';
    localMaxPrice.value = '';
    applyPriceFilter();
}
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

                        <!-- Min rating chips -->
                        <div class="flex gap-1">
                            <button
                                v-for="star in [4, 3]"
                                :key="star"
                                :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium border transition',
                                    filters?.min_rating == star
                                        ? 'bg-yellow-400 border-yellow-400 text-white'
                                        : 'border-gray-300 text-gray-600 hover:border-yellow-400'
                                ]"
                                @click="updateFilters({ min_rating: filters?.min_rating == star ? undefined : star })"
                            >
                                {{ star }}★+
                            </button>
                        </div>

                        <select
                            @change="updateFilters({ sort: $event.target.value })"
                            class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{{ lang.sort?.latest }}</option>
                            <option value="price_asc" :selected="filters?.sort === 'price_asc'">{{ lang.sort?.price_asc }}</option>
                            <option value="price_desc" :selected="filters?.sort === 'price_desc'">{{ lang.sort?.price_desc }}</option>
                            <option value="rating_desc" :selected="filters?.sort === 'rating_desc'">評分最高</option>
                        </select>
                    </div>

                    <div v-if="isLoading" role="status" aria-busy="true" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <span class="sr-only">載入中…</span>
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
