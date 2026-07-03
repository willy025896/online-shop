<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StarRating from '@/Components/StarRating.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    order: Object,
    reviewableItems: Array,
    coolingUntil: String,
})

const page = usePage()
const lang = computed(() => page.props.lang || {})

const forms = ref(
    props.reviewableItems.map((item) => ({
        item,
        form: useForm({ order_item_id: item.id, rating: 0, comment: '' }),
        submitted: false,
    }))
)

function submit(entry) {
    entry.form.post(route('reviews.store'), {
        preserveScroll: true,
        onSuccess: () => { entry.submitted = true },
    })
}
</script>

<template>
    <AppLayout :title="lang.write_review || '撰寫評論'">
        <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ lang.write_review || '撰寫評論' }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">訂單 {{ order.order_number }}</p>
            </div>

            <!-- Cooling period notice -->
            <div v-if="coolingUntil" class="mb-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-sm text-blue-800 dark:text-blue-300">
                {{ lang.cooling_notice?.replace(':time', new Date(coolingUntil).toLocaleString()) || `雙方已完成評論，評論將於 ${new Date(coolingUntil).toLocaleString()} 公開。` }}
            </div>

            <div v-if="reviewableItems.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 text-center text-gray-500 dark:text-gray-400">
                {{ lang.already_reviewed || '所有商品均已評論。' }}
            </div>

            <div v-else class="space-y-6">
                <div v-for="entry in forms" :key="entry.item.id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <div v-if="entry.submitted" class="text-center py-4 text-green-600 dark:text-green-400 font-medium">
                        ✓ 評論已送出
                    </div>
                    <template v-else>
                        <!-- Product info -->
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-md overflow-hidden flex-shrink-0">
                                <img
                                    v-if="entry.item.product?.primary_image"
                                    :src="`/storage/${entry.item.product.primary_image.path}`"
                                    :alt="entry.item.product_name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ entry.item.product_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ entry.item.quantity }} 件</div>
                            </div>
                        </div>

                        <!-- Rating -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ lang.rating || '評分' }}</label>
                            <StarRating v-model="entry.form.rating" size="lg" />
                            <InputError :message="entry.form.errors.rating" class="mt-1" />
                        </div>

                        <!-- Comment -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ lang.comment || '評論內容（選填）' }}</label>
                            <textarea
                                v-model="entry.form.comment"
                                rows="4"
                                maxlength="1000"
                                :placeholder="lang.comment_placeholder || '分享您的使用心得...'"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                            />
                            <InputError :message="entry.form.errors.comment" class="mt-1" />
                        </div>

                        <button
                            :disabled="entry.form.rating === 0 || entry.form.processing"
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition"
                            @click="submit(entry)"
                        >
                            {{ lang.submit || '送出評論' }}
                        </button>
                    </template>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a :href="route('orders.show', order.id)" class="text-sm text-indigo-600 hover:underline">← 返回訂單</a>
            </div>
        </div>
    </AppLayout>
</template>
