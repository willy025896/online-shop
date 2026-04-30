<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConversationList from '@/Components/Messages/ConversationList.vue';
import MessageThread from '@/Components/Messages/MessageThread.vue';

const props = defineProps({
    conversations: Array,
    conversation: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
</script>

<template>
    <AppLayout :title="`${lang.title} - ${conversation.other_user.name}`">
        <div class="max-w-7xl mx-auto h-[calc(100vh-4rem)] flex">
            <div class="hidden md:block w-80 lg:w-96 h-full">
                <ConversationList :conversations="conversations" :active-id="conversation.id" />
            </div>
            <div class="flex-1 h-full">
                <MessageThread :key="conversation.id" :conversation="conversation" />
            </div>
        </div>
    </AppLayout>
</template>
