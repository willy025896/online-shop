<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { useAsyncAction } from '@/Composables/useAsyncAction';
import { useToast } from '@/Composables/useToast';
import { combinationKey } from '@/Utils/variantCombination';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductImageGallery from '@/Components/ProductImageGallery.vue';
import ProductCard from '@/Components/ProductCard.vue';
import StarRating from '@/Components/StarRating.vue';
import ReviewCard from '@/Components/ReviewCard.vue';
import RatingDistribution from '@/Components/RatingDistribution.vue';
import Pagination from '@/Components/Pagination.vue';
import FavoriteButton from '@/Components/FavoriteButton.vue';
import Spinner from '@/Components/Spinner.vue';

const props = defineProps({
    product: Object,
    isAvailable: { type: Boolean, default: true },
    relatedProducts: Array,
    reviews: Object,
    ratingDistribution: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const quantity = ref(1);
const { processing: addingToCart, run } = useAsyncAction();
const { processing: asking, run: runAsk } = useAsyncAction();
const toast = useToast();

const isOwnProduct = computed(() => page.props.auth?.user?.id === props.product.shop?.user_id);

const hasVariants = computed(() => (props.product.variants || []).length > 0);
const selected = ref({});

const selectedVariant = computed(() => {
    if (!hasVariants.value) return null;

    const optionIds = (props.product.options || []).map((o) => o.id);
    if (optionIds.some((id) => !selected.value[id])) return null;

    const selectedKey = combinationKey(optionIds.map((id) => selected.value[id]));

    return (props.product.variants || []).find((variant) => (
        combinationKey(variant.option_values.map((ov) => ov.id)) === selectedKey
    )) || null;
});

// The "current" price/stock source: the product itself for a variant-less
// product, or the selected variant (null until every option is chosen) once
// the product has variants — every displayed field reads off this one place.
const activeSource = computed(() => hasVariants.value ? selectedVariant.value : props.product);

const displayPrice = computed(() => activeSource.value?.price);
const displayComparePrice = computed(() => activeSource.value?.compare_price);
const displayStock = computed(() => activeSource.value?.stock ?? 0);
const canAddToCart = computed(() => activeSource.value !== null);

// Clamp the selected quantity whenever the available stock shrinks (e.g. the
// buyer switches to a variant with less stock than the previous selection).
watch(displayStock, (stock) => {
    if (quantity.value > stock) {
        quantity.value = Math.max(stock, 1);
    }
});

const addToCart = () => {
    run((finish) => router.post(route('cart.store'), {
        product_id: props.product.id,
        variant_id: selectedVariant.value?.id,
        quantity: quantity.value,
    }, {
        preserveScroll: true,
        onError: (errors) => toast.error(errors.variant_id || errors.product),
        onFinish: finish,
    }));
};

const askSeller = () => {
    runAsk((finish) => router.post(route('products.ask', props.product.slug), {}, {
        onFinish: finish,
    }));
};
</script>

<template>
    <AppLayout :title="product.name">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <ProductImageGallery :images="product.images" :product-name="product.name" />

                <div>
                    <Link v-if="product.category" :href="route('categories.show', product.category.slug)" class="text-sm text-brand-500 hover:text-brand-700">
                        {{ product.category.name }}
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ product.name }}</h1>

                    <div v-if="product.reviews_count > 0" class="flex items-center gap-2 mt-2">
                        <StarRating :model-value="Math.round(product.rating_sum / product.reviews_count)" :readonly="true" size="sm" />
                        <span class="text-sm text-gray-500">
                            {{ (product.rating_sum / product.reviews_count).toFixed(1) }}
                            ({{ product.reviews_count }} 則評論)
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <span v-if="displayPrice != null" class="text-3xl font-bold text-red-600">${{ displayPrice }}</span>
                        <span v-if="displayComparePrice" class="text-lg text-gray-400 line-through">${{ displayComparePrice }}</span>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <Link :href="route('shops.show', product.shop.slug)" class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-500">
                            {{ (lang.sold_by || 'Sold by :name').replace(':name', product.shop.name) }}
                        </Link>
                        <button
                            v-if="!isOwnProduct"
                            @click="askSeller"
                            :disabled="asking"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-500 hover:text-brand-700 disabled:opacity-50"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                            </svg>
                            {{ lang.ask_seller || 'Ask Seller' }}
                        </button>
                    </div>

                    <div class="mt-6" v-if="!isAvailable">
                        <p class="text-red-500 font-medium">{{ lang.unavailable || 'This product is no longer available' }}</p>
                    </div>
                    <div class="mt-6" v-else>
                        <div v-if="hasVariants" class="mb-4 space-y-3">
                            <div v-for="option in product.options" :key="option.id">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ option.name }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="value in option.values"
                                        :key="value.id"
                                        type="button"
                                        @click="selected[option.id] = value.id"
                                        :class="[
                                            'px-3 py-1.5 rounded-md border text-sm transition',
                                            selected[option.id] === value.id
                                                ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300'
                                                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-400',
                                        ]"
                                    >
                                        {{ value.value }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p v-if="hasVariants && !selectedVariant" class="text-sm text-gray-500 mb-3">
                            {{ lang.select_variant || 'Please select options above' }}
                        </p>
                        <p v-else-if="displayStock > 0" class="text-sm text-green-600 mb-3">
                            {{ (lang.in_stock_count || ':count available').replace(':count', displayStock) }}
                        </p>
                        <p v-else class="text-red-500 font-medium mb-3">{{ lang.out_of_stock }}</p>

                        <div v-if="canAddToCart && displayStock > 0" class="flex items-center gap-4">
                            <select v-model="quantity" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option v-for="n in Math.min(displayStock, 10)" :key="n" :value="n">{{ n }}</option>
                            </select>
                            <button
                                @click="addToCart"
                                :disabled="addingToCart"
                                class="flex-1 flex items-center justify-center gap-2 bg-brand-500 text-white py-3 px-6 rounded-lg hover:bg-brand-600 transition font-medium disabled:opacity-50"
                            >
                                <Spinner v-if="addingToCart" class="h-4 w-4" />
                                {{ lang.add_to_cart }}
                            </button>
                            <FavoriteButton :product-id="product.id" size="md" />
                        </div>
                    </div>

                    <div v-if="product.description" class="mt-8 prose dark:prose-invert max-w-none">
                        <h3 class="text-lg font-medium">{{ lang.description }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ product.description }}</p>
                    </div>
                </div>
            </div>

            <!-- Reviews section -->
            <div class="mt-12">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">顧客評論</h2>

                <div v-if="product.reviews_count > 0" class="flex flex-col sm:flex-row gap-8 mb-8">
                    <!-- Average -->
                    <div class="text-center flex-shrink-0">
                        <div class="text-5xl font-bold text-gray-900">
                            {{ (product.rating_sum / product.reviews_count).toFixed(1) }}
                        </div>
                        <StarRating :model-value="Math.round(product.rating_sum / product.reviews_count)" :readonly="true" size="lg" />
                        <div class="text-sm text-gray-500 mt-1">{{ product.reviews_count }} 則評論</div>
                    </div>
                    <!-- Distribution -->
                    <div class="flex-1">
                        <RatingDistribution :distribution="ratingDistribution" :total="product.reviews_count" />
                    </div>
                </div>

                <div v-if="reviews.data.length === 0" class="text-center py-10 text-gray-400">
                    目前尚無評論。
                </div>

                <div v-else class="space-y-4">
                    <ReviewCard v-for="review in reviews.data" :key="review.id" :review="review" />
                </div>

                <Pagination v-if="reviews.last_page > 1" :links="reviews.links" class="mt-6" />
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
