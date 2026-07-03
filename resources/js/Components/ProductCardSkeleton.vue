<script setup>
import Skeleton from '@/Components/Skeleton.vue';
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden" aria-hidden="true">
        <div class="aspect-square bg-gray-200 dark:bg-gray-700 animate-pulse" />
        <div class="p-4 space-y-2">
            <Skeleton width="40%" height="0.75rem" />
            <Skeleton width="90%" height="0.875rem" />
            <Skeleton width="30%" height="1.25rem" />
        </div>
    </div>
</template>
