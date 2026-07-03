<script setup>
import Skeleton from '@/Components/Skeleton.vue';

defineProps({
    withGrowth: { type: Boolean, default: false },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 space-y-2" aria-hidden="true">
        <Skeleton width="60%" height="0.75rem" />
        <Skeleton width="40%" height="1.5rem" />
        <Skeleton v-if="withGrowth" width="30%" height="0.75rem" />
    </div>
</template>
