<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-5 py-2.5 bg-brand-500 dark:bg-brand-400 border border-transparent rounded-full font-semibold text-sm text-white shadow-soft hover:bg-brand-600 dark:hover:bg-brand-300 hover:shadow-glow hover:-translate-y-0.5 focus:bg-brand-600 dark:focus:bg-brand-300 active:translate-y-0 active:bg-brand-700 dark:active:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:hover:translate-y-0 disabled:hover:shadow-soft transition ease-in-out duration-150">
        <slot />
    </button>
</template>
