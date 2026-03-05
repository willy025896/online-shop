<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

const search = ref(props.modelValue);

const submit = () => {
    router.get(route('products.index'), { search: search.value }, {
        preserveState: true,
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="flex">
        <input
            v-model="search"
            type="text"
            placeholder="Search products..."
            class="flex-1 rounded-l-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        />
        <button type="submit" class="px-4 bg-indigo-600 text-white rounded-r-lg hover:bg-indigo-700 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>
    </form>
</template>
