<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    categories: Array,
    activeSlug: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="space-y-1">
        <div v-for="category in categories" :key="category.id">
            <Link
                :href="route('categories.show', category.slug)"
                :class="[
                    'block px-3 py-2 rounded-md text-sm',
                    activeSlug === category.slug
                        ? 'bg-brand-50 text-brand-600 dark:bg-brand-900/50 dark:text-brand-300 font-medium'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                ]"
            >
                {{ category.name }}
            </Link>
            <div v-if="category.children?.length" class="ml-4">
                <Link
                    v-for="child in category.children"
                    :key="child.id"
                    :href="route('categories.show', child.slug)"
                    :class="[
                        'block px-3 py-1.5 rounded-md text-sm',
                        activeSlug === child.slug
                            ? 'text-brand-500 dark:text-brand-300 font-medium'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                    ]"
                >
                    {{ child.name }}
                </Link>
            </div>
        </div>
    </div>
</template>
