<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center px-5 py-2.5 bg-red-600 border border-transparent rounded-full font-semibold text-sm text-white shadow-soft hover:bg-red-500 hover:-translate-y-0.5 active:translate-y-0 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:hover:translate-y-0 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
