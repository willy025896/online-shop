<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CartSummary from '@/Components/CartSummary.vue';

const props = defineProps({
    cart: Object,
    totals: Object,
    shopBreakdown: Array,
    shippingConfig: Object,
    user: Object,
    itemIds: Array,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const freeThreshold = computed(() => props.shippingConfig?.free_threshold ?? null);

// Shops that haven't yet reached the free-shipping threshold, with the
// remaining amount needed — used to nudge the buyer toward free shipping.
const freeShippingHints = computed(() => {
    if (freeThreshold.value == null) return [];
    return (props.shopBreakdown ?? [])
        .filter(s => s.shipping_fee > 0) // fee > 0 already implies the shop is below the threshold
        .map(s => ({ shopName: s.shop_name, remaining: (freeThreshold.value - s.subtotal).toFixed(2) }));
});

const form = useForm({
    shipping_name: props.user?.name || '',
    shipping_phone: props.user?.phone || '',
    shipping_address: '',
    payment_method: 'simulated',
    notes: '',
    item_ids: props.itemIds ?? [],
});

const submit = () => {
    form.post(route('checkout.store'));
};
</script>

<template>
    <AppLayout :title="lang.title">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ lang.title }}</h1>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ lang.shipping_info }}</h2>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ lang.name }}</label>
                                    <input v-model="form.shipping_name" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                                    <p v-if="form.errors.shipping_name" class="mt-1 text-sm text-red-500">{{ form.errors.shipping_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ lang.phone }}</label>
                                    <input v-model="form.shipping_phone" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                                    <p v-if="form.errors.shipping_phone" class="mt-1 text-sm text-red-500">{{ form.errors.shipping_phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ lang.address }}</label>
                                    <textarea v-model="form.shipping_address" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700"></textarea>
                                    <p v-if="form.errors.shipping_address" class="mt-1 text-sm text-red-500">{{ form.errors.shipping_address }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ lang.notes_optional }}</label>
                                    <textarea v-model="form.notes" rows="2" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ lang.order_items }}</h2>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                <div v-for="item in cart.items" :key="item.id" class="flex items-center gap-4 py-3">
                                    <div class="h-16 w-16 bg-gray-200 dark:bg-gray-700 rounded-md overflow-hidden flex-shrink-0">
                                        <img v-if="item.product?.primary_image" :src="`/storage/${item.product.primary_image.path}`" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.product?.name }}</p>
                                        <p class="text-xs text-gray-500">x{{ item.quantity }}</p>
                                    </div>
                                    <p class="text-sm font-medium">${{ (item.quantity * item.unit_price).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Per-shop shipping breakdown -->
                        <div v-if="shopBreakdown && shopBreakdown.length > 1" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-sm">
                            <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ lang.shipping_by_shop }}</h3>
                            <div v-for="s in shopBreakdown" :key="s.shop_id" class="flex justify-between text-gray-600 dark:text-gray-400 py-0.5">
                                <span class="truncate pr-2">{{ s.shop_name }}</span>
                                <span>{{ s.shipping_fee > 0 ? `$${Number(s.shipping_fee).toFixed(2)}` : lang.free_shipping }}</span>
                            </div>
                        </div>

                        <!-- Free-shipping nudges -->
                        <div v-if="freeShippingHints.length" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                            <p v-for="hint in freeShippingHints" :key="hint.shopName">
                                {{ lang.free_shipping_hint?.replace(':shop', hint.shopName).replace(':amount', hint.remaining) }}
                            </p>
                        </div>

                        <CartSummary :totals="totals" :show-checkout="false">
                            <div v-if="form.errors.checkout" class="mt-3 text-sm text-red-500">{{ form.errors.checkout }}</div>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="mt-4 w-full bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50"
                            >
                                {{ form.processing ? lang.processing : lang.place_order }}
                            </button>
                        </CartSummary>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
