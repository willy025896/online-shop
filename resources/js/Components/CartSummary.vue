<script setup>
defineProps({
    totals: Object,
    showCheckout: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Order Summary</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                <span>Subtotal</span>
                <span>${{ Number(totals.subtotal).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                <span>Shipping</span>
                <span>{{ totals.shipping_fee > 0 ? `$${Number(totals.shipping_fee).toFixed(2)}` : 'Free' }}</span>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                <span>Total</span>
                <span>${{ Number(totals.total).toFixed(2) }}</span>
            </div>
        </div>
        <slot />
    </div>
</template>
