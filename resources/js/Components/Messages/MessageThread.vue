<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import OrderCardBanner from './OrderCardBanner.vue';
import MessageBubble from './MessageBubble.vue';
import MessageComposer from './MessageComposer.vue';

const props = defineProps({
    conversation: Object,
});

const messages = ref([...props.conversation.messages]);
const scrollContainer = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
        }
    });
};

const isNearBottom = () => {
    if (!scrollContainer.value) return true;
    const { scrollTop, scrollHeight, clientHeight } = scrollContainer.value;
    return scrollHeight - (scrollTop + clientHeight) < 100;
};

const handleImageLoaded = () => {
    if (isNearBottom()) scrollToBottom();
};

watch(() => props.conversation.id, () => {
    messages.value = [...props.conversation.messages];
    scrollToBottom();
});

watch(() => props.conversation.messages, (newMessages) => {
    messages.value = [...newMessages];
    scrollToBottom();
}, { deep: true });

let echoChannel = null;

const subscribe = (id) => {
    if (echoChannel) {
        window.Echo.leave(`conversation.${echoChannel}`);
    }
    echoChannel = id;
    window.Echo.private(`conversation.${id}`)
        .listen('MessageSent', (e) => {
            const exists = messages.value.some(m => m.id === e.message.id);
            if (!exists) {
                messages.value.push(e.message);
                scrollToBottom();
                router.post(route('messages.read', id), {}, { preserveScroll: true, preserveState: true, only: [] });
            }
        });
};

onMounted(() => {
    subscribe(props.conversation.id);
    scrollToBottom();
});

watch(() => props.conversation.id, (newId) => {
    subscribe(newId);
});

onUnmounted(() => {
    if (echoChannel) {
        window.Echo.leave(`conversation.${echoChannel}`);
    }
});
</script>

<template>
    <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900">
        <!-- Header with other user -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center gap-3">
            <img
                :src="conversation.other_user.profile_photo_url"
                :alt="conversation.other_user.name"
                class="w-10 h-10 rounded-full object-cover"
            />
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                    {{ conversation.other_user.name }}
                </p>
            </div>
        </div>

        <!-- Order banner -->
        <OrderCardBanner :order="conversation.order" />

        <!-- Messages -->
        <div ref="scrollContainer" class="flex-1 overflow-y-auto p-4">
            <MessageBubble
                v-for="message in messages"
                :key="message.id"
                :message="message"
                @image-loaded="handleImageLoaded"
            />
        </div>

        <!-- Composer -->
        <MessageComposer :conversation-id="conversation.id" />
    </div>
</template>
