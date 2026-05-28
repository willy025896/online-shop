<script setup>
defineProps({
    products: { type: Array, default: () => [] },
    lang: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ lang.top_products }}</p>
        <div v-if="products.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
            {{ lang.top_products_empty }}
        </div>
        <table v-else class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700">
                    <th class="pb-2 text-left font-medium">#</th>
                    <th class="pb-2 text-left font-medium">{{ lang.product_name }}</th>
                    <th class="pb-2 text-right font-medium">{{ lang.qty_sold }}</th>
                    <th class="pb-2 text-right font-medium">{{ lang.revenue_col }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                <tr v-for="(p, i) in products" :key="i" class="text-gray-700 dark:text-gray-300">
                    <td class="py-2 pr-2 text-gray-400 font-medium">{{ i + 1 }}</td>
                    <td class="py-2 truncate max-w-[140px]">{{ p.product_name }}</td>
                    <td class="py-2 text-right">{{ p.qty }}</td>
                    <td class="py-2 text-right font-medium">${{ Number(p.revenue).toFixed(2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
