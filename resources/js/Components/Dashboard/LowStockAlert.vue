<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    products: { type: Array, default: () => [] },
    count: { type: Number, default: 0 },
    lang: { type: Object, default: () => ({}) },
});

const remaining = computed(() => props.count - props.products.length);
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ lang.low_stock }}
                <span
                    v-if="count > 0"
                    class="ml-2 inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 text-xs font-bold"
                >{{ count }}</span>
            </h3>
            <Link
                v-if="count > 0"
                :href="route('seller.products.index', { low_stock: 1 })"
                class="text-xs text-indigo-600 hover:text-indigo-800"
            >{{ lang.view_all }}</Link>
        </div>

        <div v-if="count === 0" class="px-6 py-8 text-center text-sm text-gray-500">
            {{ lang.low_stock_empty }}
        </div>
        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
            <Link
                v-for="product in products"
                :key="product.id"
                :href="route('seller.products.edit', product.id)"
                class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50"
            >
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate max-w-[200px]">{{ product.name }}</p>
                <span
                    :class="[
                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                        product.stock === 0 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'
                    ]"
                >
                    {{ product.stock === 0 ? lang.out_of_stock : lang.stock_left?.replace(':n', product.stock) }}
                </span>
            </Link>
            <div v-if="remaining > 0" class="px-6 py-2 text-center text-xs text-gray-500">
                {{ lang.low_stock_more?.replace(':n', remaining) }}
            </div>
        </div>
    </div>
</template>
