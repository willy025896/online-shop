<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CartSummary from '@/Components/CartSummary.vue';

const props = defineProps({
    cart: Object,
    totals: Object,
    user: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const form = useForm({
    shipping_name: props.user?.name || '',
    shipping_phone: props.user?.phone || '',
    shipping_address: '',
    payment_method: 'simulated',
    notes: '',
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

                    <div>
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
