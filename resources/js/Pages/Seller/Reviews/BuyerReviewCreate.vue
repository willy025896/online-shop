<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import SellerLayout from '@/Layouts/SellerLayout.vue'
import StarRating from '@/Components/StarRating.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    order: Object,
    coolingUntil: String,
})

const page = usePage()
const lang = computed(() => page.props.lang || {})

const form = useForm({ rating: 0, comment: '' })

function submit() {
    form.post(route('seller.buyer-reviews.store', props.order.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <SellerLayout title="評價買家">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">評價買家</h2>
        </template>

        <div class="max-w-xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="mb-4">
                    <div class="text-sm text-gray-500">訂單：{{ order.order_number }}</div>
                    <div class="text-sm text-gray-500">買家：{{ order.user?.name }}</div>
                </div>

                <!-- Cooling notice -->
                <div v-if="coolingUntil" class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                    雙方已完成評論，評論將於 {{ new Date(coolingUntil).toLocaleString() }} 公開。
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">評分</label>
                    <StarRating v-model="form.rating" size="lg" />
                    <InputError :message="form.errors.rating" class="mt-1" />
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">評論（選填）</label>
                    <textarea
                        v-model="form.comment"
                        rows="4"
                        maxlength="1000"
                        placeholder="評價這位買家的交易體驗..."
                        class="w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                    />
                    <InputError :message="form.errors.comment" class="mt-1" />
                </div>

                <button
                    :disabled="form.rating === 0 || form.processing"
                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition"
                    @click="submit"
                >
                    送出評價
                </button>
            </div>

            <div class="mt-4 text-center">
                <a :href="route('seller.orders.show', order.id)" class="text-sm text-indigo-600 hover:underline">← 返回訂單</a>
            </div>
        </div>
    </SellerLayout>
</template>
