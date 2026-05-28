<script setup>
defineProps({
    counts: { type: Object, default: () => ({}) },
    lang: { type: Object, default: () => ({}) },
});

const statusColor = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-blue-100 text-blue-800',
    processing: 'bg-indigo-100 text-indigo-800',
    shipped: 'bg-purple-100 text-purple-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 h-full">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ lang.order_status_dist }}</p>
        <div class="grid grid-cols-2 gap-2">
            <div
                v-for="(count, status) in counts"
                :key="status"
                class="flex items-center justify-between rounded-md px-3 py-2"
                :class="statusColor[status] ?? 'bg-gray-100 text-gray-700'"
            >
                <span class="text-xs font-medium capitalize">{{ lang['orders']?.['statuses']?.[status] ?? status }}</span>
                <span class="text-sm font-bold">{{ count }}</span>
            </div>
        </div>
    </div>
</template>
