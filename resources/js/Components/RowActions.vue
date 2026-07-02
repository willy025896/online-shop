<script setup>
import Spinner from '@/Components/Spinner.vue';

defineProps({
    loading: Boolean,
});
</script>

<template>
    <Spinner v-if="loading" class="h-4 w-4 inline text-gray-400" />
    <template v-else>
        <slot />
    </template>
</template>
