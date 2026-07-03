<script setup>
defineProps({
    width: { type: String, default: '100%' },
    height: { type: String, default: '1rem' },
    rounded: { type: String, default: 'rounded' },
});
</script>

<template>
    <div class="animate-pulse bg-gray-200 dark:bg-gray-700" :class="rounded" :style="{ width, height }" aria-hidden="true" />
</template>
