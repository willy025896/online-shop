<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import DialogModal from '@/Components/DialogModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import StarRating from '@/Components/StarRating.vue';
import Spinner from '@/Components/Spinner.vue';
import { Link } from '@inertiajs/vue3';
import { useReviewCountdown } from '@/Composables/useReviewCountdown';
import { useAsyncAction } from '@/Composables/useAsyncAction';

const props = defineProps({
    order: Object,
    canSellerCancel: Boolean,
    nextStatuses: Object,
    buyerRating: Object,
    canReviewBuyer: Boolean,
});

const reviewDaysLeft = useReviewCountdown(() => props.order);

const page = usePage();
const lang = computed(() => page.props.lang || {});
const t = computed(() => lang.value.orders || {});

const cancellation = computed(() => props.order.latest_cancellation);
const isCancelled = computed(() => props.order.status === 'cancelled');
const requestPending = computed(() => cancellation.value?.status === 'requested');
const canSellerCancel = computed(() => props.canSellerCancel);

const { processing: updatingStatus, run: runUpdateStatus } = useAsyncAction();
const updateStatus = (status) => {
    runUpdateStatus((finish) => router.patch(route('seller.orders.status', props.order.id), { status }, {
        onFinish: finish,
    }));
};

const { processing: replying, run: runReply } = useAsyncAction();
const replyCustomer = () => {
    runReply((finish) => router.post(route('orders.conversation', props.order.id), {}, {
        onFinish: finish,
    }));
};

const { processing: approving, run: runApprove } = useAsyncAction();
const approve = () => {
    runApprove((finish) => router.post(route('seller.orders.cancellation.approve', props.order.id), {}, {
        preserveScroll: true,
        onFinish: finish,
    }));
};

const showRejectModal = ref(false);
const rejectForm = useForm({ response_reason: '' });
const openRejectModal = () => {
    rejectForm.reset();
    rejectForm.clearErrors();
    showRejectModal.value = true;
};
const submitReject = () => {
    rejectForm.post(route('seller.orders.cancellation.reject', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { showRejectModal.value = false; },
    });
};

const showCancelModal = ref(false);
const cancelForm = useForm({ reason: '' });
const openCancelModal = () => {
    cancelForm.reset();
    cancelForm.clearErrors();
    showCancelModal.value = true;
};
const submitCancel = () => {
    cancelForm.post(route('seller.orders.cancel', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { showCancelModal.value = false; },
    });
};

const nextStatuses = props.nextStatuses;
</script>

<template>
    <SellerLayout :title="t.details">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ order.order_number }}</h2>
                <div class="flex items-center gap-3">
                    <OrderStatusBadge :status="order.status" />
                    <button
                        @click="replyCustomer"
                        :disabled="replying"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700 transition disabled:opacity-50"
                    >
                        <Spinner v-if="replying" class="h-3.5 w-3.5" />
                        {{ t.reply_customer || 'Reply Customer' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-4xl space-y-6">
            <!-- Pending cancellation request -->
            <div v-if="requestPending" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-300 mb-2">{{ t.cancellation_requested }}</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                    <span class="font-medium">{{ t.cancel_reason }}:</span> {{ cancellation.reason }}
                </p>
                <div class="flex gap-3">
                    <DangerButton :disabled="approving" class="inline-flex items-center gap-2" @click="approve">
                        <Spinner v-if="approving" class="h-4 w-4" />
                        {{ t.approve_cancellation }}
                    </DangerButton>
                    <SecondaryButton @click="openRejectModal">{{ t.reject_cancellation }}</SecondaryButton>
                </div>
            </div>

            <!-- Cancelled info -->
            <div v-else-if="isCancelled && cancellation" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                <h3 class="text-lg font-medium text-red-800 dark:text-red-300 mb-2">
                    {{ cancellation.initiated_by === 'seller' ? t.cancelled_by_seller : t.cancelled_by_buyer }}
                </h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">{{ t.cancel_reason }}:</span> {{ cancellation.reason }}
                </p>
            </div>

            <!-- Review window notice -->
            <div
                v-if="canReviewBuyer && reviewDaysLeft !== null"
                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4"
            >
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <div class="flex-1 text-sm text-yellow-800 dark:text-yellow-200">
                        <p>
                            {{ reviewDaysLeft === 0
                                ? (lang.window_today_notice || '今天是此訂單評論的最後一天，請把握時間。')
                                : (lang.window_open_notice_seller || '您還有 :days 天可評價此買家，逾期窗口將永久關閉。').replace(':days', reviewDaysLeft) }}
                        </p>
                        <Link
                            :href="route('seller.buyer-reviews.create', order.id)"
                            class="inline-block mt-2 px-3 py-1.5 bg-yellow-500 text-white text-xs font-medium rounded hover:bg-yellow-600 transition"
                        >
                            ★ {{ lang.review_buyer || '評價買家' }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t.customer_info }}</h3>
                    <Link
                        v-if="buyerRating"
                        :href="route('seller.buyers.show', order.user?.id)"
                        class="flex items-center gap-2 text-sm text-gray-600 hover:text-indigo-600"
                    >
                        <StarRating :model-value="Math.round(buyerRating.average)" :readonly="true" size="sm" />
                        <span>{{ buyerRating.average.toFixed(1) }} ({{ buyerRating.count }})</span>
                    </Link>
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.name }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.user?.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.email }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.user?.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.shipping_name }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.phone }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_phone }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ t.address }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ order.shipping_address }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Order Items -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t.order_items }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.product }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.price }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.qty }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.subtotal }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ item.product?.name || item.product_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${{ Number(item.unit_price).toFixed(2) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ item.quantity }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-gray-100">${{ Number(item.subtotal).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-right space-y-1">
                    <p class="text-sm text-gray-500">{{ t.subtotal }}: ${{ Number(order.subtotal).toFixed(2) }}</p>
                    <p v-if="Number(order.discount) > 0" class="text-sm text-green-600">{{ t.discount }}<span v-if="order.coupon_code"> ({{ order.coupon_code }})</span>: -${{ Number(order.discount).toFixed(2) }}</p>
                    <p class="text-sm text-gray-500">{{ t.shipping }}: ${{ Number(order.shipping_fee).toFixed(2) }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ t.total }}: ${{ Number(order.total).toFixed(2) }}</p>
                </div>
            </div>

            <!-- Update Status -->
            <div v-if="nextStatuses[order.status]" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ t.update_status }}</h3>
                <div class="flex gap-3">
                    <button
                        @click="updateStatus(nextStatuses[order.status])"
                        :disabled="updatingStatus"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <Spinner v-if="updatingStatus" class="h-4 w-4" />
                        {{ t.mark_as?.replace(':status', nextStatuses[order.status]) }}
                    </button>
                    <button
                        v-if="canSellerCancel"
                        @click="openCancelModal"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700"
                    >
                        {{ t.cancel_order }}
                    </button>
                </div>
            </div>
            <div v-else-if="canSellerCancel" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <button
                    @click="openCancelModal"
                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700"
                >
                    {{ t.cancel_order }}
                </button>
            </div>

            <!-- Notes -->
            <div v-if="order.notes" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ t.notes }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.notes }}</p>
            </div>
        </div>

        <!-- Reject request modal -->
        <DialogModal :show="showRejectModal" @close="showRejectModal = false">
            <template #title>{{ t.reject_cancellation }}</template>
            <template #content>
                <p class="mb-3">{{ t.reject_reason_prompt }}</p>
                <textarea
                    v-model="rejectForm.response_reason"
                    rows="4"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                ></textarea>
                <InputError :message="rejectForm.errors.response_reason" class="mt-2" />
            </template>
            <template #footer>
                <SecondaryButton @click="showRejectModal = false">{{ t.cancel }}</SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': rejectForm.processing }"
                    :disabled="rejectForm.processing"
                    @click="submitReject"
                >
                    {{ t.confirm }}
                </DangerButton>
            </template>
        </DialogModal>

        <!-- Seller cancel modal -->
        <DialogModal :show="showCancelModal" @close="showCancelModal = false">
            <template #title>{{ t.cancel_order }}</template>
            <template #content>
                <p class="mb-3">{{ t.cancel_reason_prompt }}</p>
                <textarea
                    v-model="cancelForm.reason"
                    rows="4"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                ></textarea>
                <InputError :message="cancelForm.errors.reason" class="mt-2" />
            </template>
            <template #footer>
                <SecondaryButton @click="showCancelModal = false">{{ t.cancel }}</SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': cancelForm.processing }"
                    :disabled="cancelForm.processing"
                    @click="submitCancel"
                >
                    {{ t.confirm }}
                </DangerButton>
            </template>
        </DialogModal>
    </SellerLayout>
</template>
