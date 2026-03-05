<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <nav v-if="links && links.length > 3" class="flex items-center justify-center space-x-1">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[
                    'px-3 py-2 text-sm rounded-md',
                    link.active
                        ? 'bg-indigo-600 text-white'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
                preserve-scroll
            />
            <span
                v-else
                v-html="link.label"
                class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500"
            />
        </template>
    </nav>
</template>
