<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CartItemRow from '@/Components/CartItemRow.vue';
import CartSummary from '@/Components/CartSummary.vue';

defineProps({
    cart: Object,
    totals: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
</script>

<template>
    <AppLayout :title="lang.title">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ lang.title }}</h1>

            <div v-if="cart?.items?.length" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <CartItemRow v-for="item in cart.items" :key="item.id" :item="item" />
                </div>
                <div>
                    <CartSummary :totals="totals">
                        <Link
                            :href="route('checkout.index')"
                            class="mt-4 block w-full text-center bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 transition font-medium"
                        >
                            {{ lang.checkout }}
                        </Link>
                    </CartSummary>
                </div>
            </div>

            <div v-else class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                </svg>
                <h2 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ lang.empty }}</h2>
                <Link :href="route('products.index')" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">
                    {{ lang.continue_shopping }}
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
