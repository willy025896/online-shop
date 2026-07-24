<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-5 py-2.5 bg-white dark:bg-gray-800 border border-brand-200 dark:border-brand-600 rounded-full font-semibold text-sm text-brand-500 dark:text-brand-100 shadow-soft hover:bg-brand-50 dark:hover:bg-gray-700 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 disabled:hover:translate-y-0 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
