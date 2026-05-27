<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import DialogModal from '@/Components/DialogModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    order: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const cancellation = computed(() => props.order.latest_cancellation);
const isCancelled = computed(() => props.order.status === 'cancelled');
const requestPending = computed(() => cancellation.value?.status === 'requested');
const wasRejected = computed(() => cancellation.value?.status === 'rejected');
const canDirect = computed(() => ['pending', 'paid'].includes(props.order.status));
const canRequest = computed(() =>
    ['processing', 'shipped'].includes(props.order.status) && !requestPending.value && !wasRejected.value
);
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

const pay = (orderId) => {
    router.post(route('orders.pay', orderId));
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
                                <img v-if="item.product_image" :src="`/storage/${item.product_image}`" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.product_name }}</p>
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
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>{{ lang.shipping }}</span>
                        <span>{{ order.shipping_fee > 0 ? `$${Number(order.shipping_fee).toFixed(2)}` : lang.free }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-gray-100">
                        <span>{{ lang.total }}</span>
                        <span>${{ Number(order.total).toFixed(2) }}</span>
                    </div>
                </div>

                <div v-if="order.notes" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">{{ lang.notes }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.notes }}</p>
                </div>

                <div class="flex gap-3 mt-6">
                    <button
                        v-if="order.status === 'pending' && !order.paid_at"
                        @click="pay(order.id)"
                        class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 transition text-sm font-medium"
                    >
                        {{ lang.pay_now_simulated }}
                    </button>
                    <button
                        v-if="showCancelButton"
                        @click="openCancelModal"
                        class="bg-red-600 text-white py-2 px-6 rounded-lg hover:bg-red-700 transition text-sm font-medium"
                    >
                        {{ cancelLabel }}
                    </button>
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
    </AppLayout>
</template>
