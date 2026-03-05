<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    item: Object,
});

const quantity = ref(props.item.quantity);

const updateQuantity = () => {
    router.patch(route('cart.update', props.item.id), {
        quantity: quantity.value,
    }, { preserveScroll: true });
};

const removeItem = () => {
    router.delete(route('cart.destroy', props.item.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex items-center gap-4 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="h-20 w-20 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded-md overflow-hidden">
            <img
                v-if="item.product?.primary_image"
                :src="`/storage/${item.product.primary_image.path}`"
                class="w-full h-full object-cover"
            />
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ item.product?.name }}</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.product?.shop?.name }}</p>
            <p class="text-sm font-semibold text-red-600 mt-1">${{ item.unit_price }}</p>
        </div>
        <div class="flex items-center gap-2">
            <select v-model="quantity" @change="updateQuantity" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                <option v-for="n in 99" :key="n" :value="n">{{ n }}</option>
            </select>
        </div>
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 w-20 text-right">
            ${{ (item.quantity * item.unit_price).toFixed(2) }}
        </div>
        <button @click="removeItem" class="text-gray-400 hover:text-red-500">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </div>
</template>
