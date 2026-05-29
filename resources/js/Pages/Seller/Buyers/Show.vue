<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import SellerLayout from '@/Layouts/SellerLayout.vue'
import StarRating from '@/Components/StarRating.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    buyer: Object,
    buyerRating: Object,
    reviews: Object,
})

const page = usePage()
const lang = computed(() => page.props.lang || {})
</script>

<template>
    <SellerLayout :title="`買家信用：${buyer.name}`">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">買家信用</h2>
        </template>

        <div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Buyer summary -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl flex-shrink-0">
                        {{ buyer.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 text-lg">{{ buyer.name }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <StarRating :model-value="Math.round(buyerRating.average)" :readonly="true" size="sm" />
                            <span class="text-sm text-gray-600">
                                {{ buyerRating.count > 0 ? buyerRating.average.toFixed(1) : '—' }}
                                ({{ buyerRating.count }} 則評論)
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review list -->
            <div v-if="reviews.data.length === 0" class="text-center py-12 text-gray-400">
                此買家目前尚無公開評論紀錄。
            </div>

            <div v-else class="space-y-4">
                <div v-for="review in reviews.data" :key="review.id" class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-4 mb-2">
                        <div class="text-sm text-gray-500">{{ review.shop?.name }} • {{ new Date(review.created_at).toLocaleDateString() }}</div>
                        <StarRating :model-value="review.rating" :readonly="true" size="sm" />
                    </div>
                    <p v-if="review.comment" class="text-sm text-gray-700">{{ review.comment }}</p>
                    <p v-else class="text-sm text-gray-400 italic">（無文字評論）</p>
                </div>
            </div>

            <Pagination v-if="reviews.last_page > 1" :links="reviews.links" class="mt-6" />
        </div>
    </SellerLayout>
</template>
