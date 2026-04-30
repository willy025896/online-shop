<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConversationList from '@/Components/Messages/ConversationList.vue';

defineProps({
    conversations: Array,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
</script>

<template>
    <AppLayout :title="lang.title">
        <div class="max-w-7xl mx-auto h-[calc(100vh-4rem)] flex">
            <div class="w-full md:w-80 lg:w-96 h-full">
                <ConversationList :conversations="conversations" />
            </div>
            <div class="hidden md:flex flex-1 items-center justify-center bg-gray-50 dark:bg-gray-900 text-sm text-gray-500">
                {{ lang.select_conversation || 'Select a conversation to start messaging' }}
            </div>
        </div>
    </AppLayout>
</template>
