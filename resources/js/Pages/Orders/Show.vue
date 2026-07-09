<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import StarRating from '@/Components/StarRating.vue';
import { useReviewCountdown } from '@/Composables/useReviewCountdown';
import DialogModal from '@/Components/DialogModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import ImageWithFallback from '@/Components/ImageWithFallback.vue';

const props = defineProps({
    order: Object,
    canCancelDirectly: Boolean,
    canRequestCancellation: Boolean,
    canRequestReturn: Boolean,
});

const isCompleted = computed(() => props.order.status === 'completed');
const reviewWindowOpen = computed(() => !props.order.review_released_at);
const canWriteReview = computed(() => isCompleted.value && reviewWindowOpen.value);

const reviewDaysLeft = useReviewCountdown(() => props.order);

const page = usePage();
const lang = computed(() => page.props.lang || {});

const cancellation = computed(() => props.order.latest_cancellation);
const isCancelled = computed(() => props.order.status === 'cancelled');
const requestPending = computed(() => cancellation.value?.status === 'requested');
const wasRejected = computed(() => cancellation.value?.status === 'rejected');
const canDirect = computed(() => props.canCancelDirectly);
const canRequest = computed(() => props.canRequestCancellation);
const showCancelButton = computed(() => !isCancelled.value && (canDirect.value || canRequest.value));
const cancelLabel = computed(() => (canDirect.value ? lang.value.cancel_order : lang.value.request_cancellation));

const showCancelModal = ref(false);
const cancelForm = useForm({ reason: '' });

const openCancelModal = () => {
    cancelForm.reset();
    cancelForm.clearErrors();
    showCancelModal.value = true;
};

const submitCancel = () => {
    cancelForm.post(route('orders.cancel', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { showCancelModal.value = false; },
    });
};

const orderReturn = computed(() => props.order.latest_return);
const returnPending = computed(() => orderReturn.value?.status === 'requested');
const returnApproved = computed(() => orderReturn.value?.status === 'approved');
const returnRejected = computed(() => orderReturn.value?.status === 'rejected');
const showReturnButton = computed(() => props.canRequestReturn);

const showReturnModal = ref(false);
const returnQuantities = ref({});
const returnForm = useForm({ reason: '', items: [] });

const openReturnModal = () => {
    returnForm.reset();
    returnForm.clearErrors();
    returnQuantities.value = Object.fromEntries(props.order.items.map((item) => [item.id, 0]));
    showReturnModal.value = true;
};

const submitReturn = () => {
    returnForm.items = Object.entries(returnQuantities.value)
        .filter(([, quantity]) => Number(quantity) > 0)
        .map(([orderItemId, quantity]) => ({ order_item_id: Number(orderItemId), quantity: Number(quantity) }));

    returnForm.post(route('orders.returns.store', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { showReturnModal.value = false; },
    });
};

const pay = (orderId) => {
    window.location.href = route('orders.pay', orderId);
};

const askSeller = (orderId) => {
    router.post(route('orders.conversation', orderId));
};
</script>

<template>
    <AppLayout :title="`Order ${order.order_number}`">
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ order.order_number }}</h1>
                        <p class="text-sm text-gray-500">{{ new Date(order.created_at).toLocaleString() }}</p>
                    </div>
                    <OrderStatusBadge :status="order.status" />
                </div>

                <!-- Review window notice -->
                <div
                    v-if="canWriteReview && reviewDaysLeft !== null"
                    class="mb-6 rounded-lg p-4 border bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800"
                >
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <div class="flex-1 text-sm text-yellow-800 dark:text-yellow-200">
                            {{ reviewDaysLeft === 0
                                ? (lang.window_today_notice || '今天是此訂單評論的最後一天，請把握時間。')
                                : (lang.window_open_notice || '此訂單還有 :days 天可評論，逾期窗口將永久關閉。').replace(':days', reviewDaysLeft) }}
                        </div>
                    </div>
                </div>

                <!-- Cancellation status -->
                <div
                    v-if="cancellation"
                    class="mb-6 rounded-lg p-4 border"
                    :class="{
                        'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800': requestPending,
                        'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800': isCancelled,
                        'bg-gray-50 border-gray-200 dark:bg-gray-700/40 dark:border-gray-600': wasRejected,
                    }"
                >
                    <p v-if="requestPending" class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                        {{ lang.awaiting_seller_review }}
                    </p>
                    <p v-else-if="isCancelled" class="text-sm font-medium text-red-800 dark:text-red-300">
                        {{ cancellation.initiated_by === 'seller' ? lang.cancelled_by_seller : lang.cancelled_by_buyer }}
                    </p>
                    <p v-else-if="wasRejected" class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ lang.cancellation_rejected }}
                    </p>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ lang.cancel_reason }}:</span> {{ cancellation.reason }}
                    </p>
                    <p v-if="wasRejected && cancellation.response_reason" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ lang.reject_reason }}:</span> {{ cancellation.response_reason }}
                    </p>
                </div>

                <!-- Return status -->
                <div
                    v-if="orderReturn"
                    class="mb-6 rounded-lg p-4 border"
                    :class="{
                        'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800': returnPending,
                        'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800': returnApproved,
                        'bg-gray-50 border-gray-200 dark:bg-gray-700/40 dark:border-gray-600': returnRejected,
                    }"
                >
                    <p v-if="returnPending" class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                        {{ lang.return_awaiting_seller_review }}
                    </p>
                    <p v-else-if="returnApproved" class="text-sm font-medium text-green-800 dark:text-green-300">
                        {{ (lang.return_approved || '').replace(':amount', Number(orderReturn.refund_amount).toFixed(2)) }}
                    </p>
                    <p v-else-if="returnRejected" class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ lang.return_rejected }}
                    </p>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ lang.return_reason }}:</span> {{ orderReturn.reason }}
                    </p>
                    <p v-if="returnRejected && orderReturn.response_reason" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ lang.return_reject_reason }}:</span> {{ orderReturn.response_reason }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">{{ lang.shop }}</h3>
                        <p class="text-gray-900 dark:text-gray-100">{{ order.shop?.name }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">{{ lang.shipping_to }}</h3>
                        <p class="text-gray-900 dark:text-gray-100">{{ order.shipping_name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.shipping_phone }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.shipping_address }}</p>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-4">{{ lang.order_items }}</h3>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4 py-3">
                            <div class="h-16 w-16 bg-gray-200 dark:bg-gray-700 rounded-md overflow-hidden flex-shrink-0">
                                <ImageWithFallback :src="item.product_image ? `/storage/${item.product_image}` : null" :alt="item.product_name" icon-class="h-6 w-6" loading="lazy" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.product_name }}</p>
                                <p v-if="item.variant_label" class="text-xs text-gray-500">{{ item.variant_label }}</p>
                                <p class="text-xs text-gray-500">${{ item.unit_price }} x {{ item.quantity }}</p>
                            </div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ Number(item.subtotal).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>{{ lang.subtotal }}</span>
                        <span>${{ Number(order.subtotal).toFixed(2) }}</span>
                    </div>
                    <div v-if="Number(order.discount) > 0" class="flex justify-between text-sm text-green-600 mb-1">
                        <span>{{ lang.discount }}<span v-if="order.coupon_code" class="text-xs text-gray-400"> ({{ order.coupon_code }})</span></span>
                        <span>-${{ Number(order.discount).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>{{ lang.shipping }}</span>
                        <span>{{ order.shipping_fee > 0 ? `$${Number(order.shipping_fee).toFixed(2)}` : lang.free }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-gray-100">
                        <span>{{ lang.total }}</span>
                        <span>${{ Number(order.total).toFixed(2) }}</span>
                    </div>
                    <div v-if="Number(order.refunded_amount) > 0" class="flex justify-between text-sm text-green-600 mt-1">
                        <span>{{ lang.refunded_amount }}</span>
                        <span>-${{ Number(order.refunded_amount).toFixed(2) }}</span>
                    </div>
                </div>

                <div v-if="order.notes" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">{{ lang.notes }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.notes }}</p>
                </div>

                <div class="flex gap-3 mt-6 flex-wrap">
                    <button
                        v-if="order.status === 'pending' && !order.paid_at"
                        @click="pay(order.id)"
                        class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 transition text-sm font-medium"
                    >
                        {{ lang.pay_now }}
                    </button>
                    <button
                        v-if="showCancelButton"
                        @click="openCancelModal"
                        class="bg-red-600 text-white py-2 px-6 rounded-lg hover:bg-red-700 transition text-sm font-medium"
                    >
                        {{ cancelLabel }}
                    </button>
                    <button
                        v-if="showReturnButton"
                        @click="openReturnModal"
                        class="bg-amber-600 text-white py-2 px-6 rounded-lg hover:bg-amber-700 transition text-sm font-medium"
                    >
                        {{ lang.request_return }}
                    </button>
                    <a
                        v-if="canWriteReview"
                        :href="route('reviews.create', order.id)"
                        class="bg-yellow-500 text-white py-2 px-6 rounded-lg hover:bg-yellow-600 transition text-sm font-medium"
                    >
                        ★ {{ lang.write_review || '撰寫評論' }}
                    </a>
                    <button
                        @click="askSeller(order.id)"
                        class="bg-indigo-600 text-white py-2 px-6 rounded-lg hover:bg-indigo-700 transition text-sm font-medium ms-auto"
                    >
                        {{ lang.ask_seller || 'Ask Seller' }}
                    </button>
                </div>
            </div>
        </div>

        <DialogModal :show="showCancelModal" @close="showCancelModal = false">
            <template #title>{{ cancelLabel }}</template>
            <template #content>
                <p class="mb-3">{{ lang.cancel_reason_prompt }}</p>
                <textarea
                    v-model="cancelForm.reason"
                    rows="4"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    :placeholder="lang.cancel_reason_placeholder"
                ></textarea>
                <InputError :message="cancelForm.errors.reason" class="mt-2" />
            </template>
            <template #footer>
                <SecondaryButton @click="showCancelModal = false">{{ lang.cancel }}</SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': cancelForm.processing }"
                    :disabled="cancelForm.processing"
                    @click="submitCancel"
                >
                    {{ lang.confirm }}
                </DangerButton>
            </template>
        </DialogModal>

        <DialogModal :show="showReturnModal" @close="showReturnModal = false">
            <template #title>{{ lang.request_return }}</template>
            <template #content>
                <p class="mb-3">{{ lang.return_select_items }}</p>
                <div class="divide-y divide-gray-200 dark:divide-gray-700 border-t border-b border-gray-200 dark:border-gray-700 mb-4">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        v-show="item.returnable_quantity > 0"
                        class="flex items-center gap-4 py-3"
                    >
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.product_name }}</p>
                            <p v-if="item.variant_label" class="text-xs text-gray-500">{{ item.variant_label }}</p>
                            <p class="text-xs text-gray-500">${{ item.unit_price }} x {{ item.returnable_quantity }}</p>
                        </div>
                        <input
                            v-model.number="returnQuantities[item.id]"
                            type="number"
                            min="0"
                            :max="item.returnable_quantity"
                            class="w-20 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                    </div>
                </div>
                <InputError :message="returnForm.errors.items" class="mb-2" />
                <p class="mb-2">{{ lang.return_reason }}</p>
                <textarea
                    v-model="returnForm.reason"
                    rows="3"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    :placeholder="lang.return_reason_placeholder"
                ></textarea>
                <InputError :message="returnForm.errors.reason" class="mt-2" />
            </template>
            <template #footer>
                <SecondaryButton @click="showReturnModal = false">{{ lang.cancel }}</SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': returnForm.processing }"
                    :disabled="returnForm.processing"
                    @click="submitReturn"
                >
                    {{ lang.confirm }}
                </DangerButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
