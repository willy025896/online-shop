<script setup>
defineProps({
    period: String,
    lang: { type: Object, default: () => ({}) },
});

defineEmits(['change']);

const periods = ['today', 'week', 'month', 'all'];
</script>

<template>
    <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 w-fit">
        <button
            v-for="p in periods"
            :key="p"
            @click="$emit('change', p)"
            :class="[
                'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                period === p
                    ? 'bg-white dark:bg-gray-800 text-brand-500 shadow-sm'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
            ]"
        >
            {{ lang['period_' + p] }}
        </button>
    </div>
</template>
