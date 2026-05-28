<script setup>
defineProps({
    label: String,
    value: [String, Number],
    growth: { default: null, validator: (v) => v === null || typeof v === 'number' },
    color: { type: String, default: 'gray' },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ label }}</p>
        <p :class="[
            'text-2xl font-bold mt-1',
            color === 'green' ? 'text-green-600' : color === 'yellow' ? 'text-yellow-600' : 'text-gray-900 dark:text-gray-100'
        ]">{{ value }}</p>
        <div v-if="growth !== null" class="mt-1 flex items-center gap-1 text-xs font-medium">
            <span :class="growth >= 0 ? 'text-green-600' : 'text-red-500'">
                {{ growth >= 0 ? '▲' : '▼' }} {{ Math.abs(growth) }}%
            </span>
        </div>
    </div>
</template>
