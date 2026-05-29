<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import SellerLayout from '@/Layouts/SellerLayout.vue'
import StarRating from '@/Components/StarRating.vue'
import ReviewCard from '@/Components/ReviewCard.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    reviews: Object,
    filters: Object,
    shopRating: Object,
})

const page = usePage()
const lang = computed(() => page.props.lang || {})

const filterRating = ref(props.filters?.rating ?? '')
const filterReplied = ref(props.filters?.replied ?? '')

function applyFilters() {
    router.get(route('seller.reviews.index'), {
        rating: filterRating.value || undefined,
        replied: filterReplied.value || undefined,
    }, { preserveScroll: true, replace: true })
}
</script>

<template>
    <SellerLayout title="商品評論">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">商品評論</h2>
        </template>

        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Shop aggregate rating -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6 flex items-center gap-6">
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900">
                        {{ shopRating.count > 0 ? shopRating.average.toFixed(1) : '—' }}
                    </div>
                    <StarRating :model-value="Math.round(shopRating.average)" :readonly="true" size="md" />
                    <div class="text-sm text-gray-500 mt-1">{{ shopRating.count }} 則評論</div>
                </div>
                <div class="h-12 w-px bg-gray-200"></div>
                <div class="text-sm text-gray-600">賣場整體評分</div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-6">
                <select
                    v-model="filterRating"
                    class="border border-gray-300 rounded-md text-sm px-3 py-1.5"
                    @change="applyFilters"
                >
                    <option value="">全部星等</option>
                    <option v-for="n in [5,4,3,2,1]" :key="n" :value="n">{{ n }} 星</option>
                </select>
                <select
                    v-model="filterReplied"
                    class="border border-gray-300 rounded-md text-sm px-3 py-1.5"
                    @change="applyFilters"
                >
                    <option value="">全部</option>
                    <option value="no">未回覆</option>
                    <option value="yes">已回覆</option>
                </select>
            </div>

            <!-- Reviews list -->
            <div v-if="reviews.data.length === 0" class="text-center py-12 text-gray-400">
                目前沒有符合條件的評論。
            </div>

            <div v-else class="space-y-4">
                <div v-for="review in reviews.data" :key="review.id">
                    <!-- Product name -->
                    <div class="text-xs text-gray-400 mb-1">{{ review.product?.name }}</div>
                    <ReviewCard
                        :review="review"
                        :show-reply-form="!review.seller_reply"
                        :reply-route="route('seller.reviews.reply', review.id)"
                    />
                </div>
            </div>

            <Pagination v-if="reviews.last_page > 1" :links="reviews.links" class="mt-6" />
        </div>
    </SellerLayout>
</template>
