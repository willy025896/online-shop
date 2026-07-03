<template>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
        <!-- Reviewer info + rating -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center font-semibold text-indigo-600 dark:text-indigo-400 text-sm flex-shrink-0">
                    {{ review.user?.name?.charAt(0)?.toUpperCase() }}
                </div>
                <div>
                    <div class="font-medium text-gray-800 dark:text-gray-200 text-sm">{{ review.user?.name }}</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(review.created_at) }}</div>
                </div>
            </div>
            <StarRating :model-value="review.rating" :readonly="true" size="sm" />
        </div>

        <!-- Comment -->
        <p v-if="review.comment" class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ review.comment }}</p>
        <p v-else class="text-gray-400 dark:text-gray-500 text-sm italic">（無文字評論）</p>

        <!-- Seller reply -->
        <div v-if="review.seller_reply" class="bg-gray-50 dark:bg-gray-700/50 border-l-4 border-indigo-300 dark:border-indigo-500 rounded-r pl-3 pr-3 py-2">
            <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 mb-1">賣家回覆</div>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ review.seller_reply }}</p>
        </div>

        <!-- Reply form (seller only) -->
        <div v-if="showReplyForm && !review.seller_reply" class="pt-1">
            <form @submit.prevent="submitReply">
                <textarea
                    v-model="replyForm.reply"
                    rows="3"
                    maxlength="1000"
                    placeholder="回覆這則評論..."
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                />
                <p v-if="replyForm.errors.reply" class="text-xs text-red-600 mt-1">{{ replyForm.errors.reply }}</p>
                <div class="flex justify-end gap-2 mt-2">
                    <button type="button" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" @click="replyForm.reset()">取消</button>
                    <button
                        type="submit"
                        :disabled="!replyForm.reply.trim() || replyForm.processing"
                        class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-md disabled:opacity-50 hover:bg-indigo-700"
                    >
                        送出回覆
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import StarRating from '@/Components/StarRating.vue'

const props = defineProps({
    review: { type: Object, required: true },
    showReplyForm: { type: Boolean, default: false },
    replyRoute: { type: String, default: null },
})

const replyForm = useForm({ reply: '' })

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString()
}

function submitReply() {
    if (!props.replyRoute) return
    replyForm.post(props.replyRoute, {
        preserveScroll: true,
        onSuccess: () => replyForm.reset(),
    })
}
</script>
