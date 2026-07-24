<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import SellerLayout from '@/Layouts/SellerLayout.vue'
import StarRating from '@/Components/StarRating.vue'
import ReviewCard from '@/Components/ReviewCard.vue'
import Pagination from '@/Components/Pagination.vue'
import Skeleton from '@/Components/Skeleton.vue'
import { useInFlightLoading } from '@/Composables/useInFlightLoading'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
    reviews: Object,
    filters: Object,
    shopRating: Object,
})

const page = usePage()
const t = computed(() => page.props.lang?.reviews || {})
const toast = useToast()

const filterRating = ref(props.filters?.rating ?? '')
const filterReplied = ref(props.filters?.replied ?? '')

const { isLoading, start: startLoading, finish: finishLoading } = useInFlightLoading()

function applyFilters() {
    router.get(route('seller.reviews.index'), {
        rating: filterRating.value || undefined,
        replied: filterReplied.value || undefined,
    }, {
        preserveScroll: true,
        replace: true,
        onStart: startLoading,
        onFinish: finishLoading,
        onError: () => toast.error(t.value.filter_failed),
    })
}
</script>

<template>
    <SellerLayout :title="t.title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ t.title }}</h2>
        </template>

        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Shop aggregate rating -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 mb-6 flex items-center gap-6">
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900 dark:text-gray-100">
                        {{ shopRating.count > 0 ? shopRating.average.toFixed(1) : '—' }}
                    </div>
                    <StarRating :model-value="Math.round(shopRating.average)" :readonly="true" size="md" />
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ (t.review_count || '').replace(':count', shopRating.count) }}</div>
                </div>
                <div class="h-12 w-px bg-gray-200 dark:bg-gray-700"></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t.shop_rating }}</div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-6">
                <select
                    v-model="filterRating"
                    class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm px-3 py-1.5"
                    @change="applyFilters"
                >
                    <option value="">{{ t.all_ratings }}</option>
                    <option v-for="n in [5,4,3,2,1]" :key="n" :value="n">{{ n }} {{ t.star }}</option>
                </select>
                <select
                    v-model="filterReplied"
                    class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm px-3 py-1.5"
                    @change="applyFilters"
                >
                    <option value="">{{ t.all }}</option>
                    <option value="no">{{ t.unreplied }}</option>
                    <option value="yes">{{ t.replied }}</option>
                </select>
            </div>

            <!-- Reviews list -->
            <div v-if="isLoading" role="status" aria-busy="true" class="space-y-4">
                <span class="sr-only">{{ t.loading }}</span>
                <div v-for="n in (reviews.data.length || reviews.per_page || 5)" :key="n" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3" aria-hidden="true">
                    <Skeleton width="30%" height="0.875rem" />
                    <Skeleton width="90%" height="0.875rem" />
                    <Skeleton width="60%" height="0.875rem" />
                </div>
            </div>

            <div v-else-if="reviews.data.length === 0" class="text-center py-12 text-gray-400 dark:text-gray-500">
                {{ t.no_reviews }}
            </div>

            <div v-else class="space-y-4">
                <div v-for="review in reviews.data" :key="review.id">
                    <!-- Product name -->
                    <div class="text-xs text-gray-400 dark:text-gray-500 mb-1">{{ review.product?.name }}</div>
                    <ReviewCard
                        :review="review"
                        :show-reply-form="!review.seller_reply"
                        :reply-route="route('seller.reviews.reply', review.id)"
                    />
                </div>
            </div>

            <Pagination v-if="reviews.last_page > 1" :links="reviews.links" class="mt-6" @start="startLoading" @finish="finishLoading" />
        </div>
    </SellerLayout>
</template>
