<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: String,
});

const config = computed(() => {
    const map = {
        pending: { label: 'Pending', class: 'bg-yellow-100 text-yellow-800' },
        paid: { label: 'Paid', class: 'bg-blue-100 text-blue-800' },
        processing: { label: 'Processing', class: 'bg-indigo-100 text-indigo-800' },
        shipped: { label: 'Shipped', class: 'bg-purple-100 text-purple-800' },
        completed: { label: 'Completed', class: 'bg-green-100 text-green-800' },
        cancelled: { label: 'Cancelled', class: 'bg-red-100 text-red-800' },
    };
    return map[props.status] || { label: props.status, class: 'bg-gray-100 text-gray-800' };
});
</script>

<template>
    <span :class="[config.class, 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
        {{ config.label }}
    </span>
</template>
