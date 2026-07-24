<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    conversations: Array,
    activeId: { type: Number, default: null },
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const previewText = (conv) => {
    if (!conv.latest_message) return lang.value.no_messages_yet || 'No messages yet';
    if (conv.latest_message.image_path) return '📷 ' + (lang.value.image_label || 'Image');
    if (conv.latest_message.product_id) return '🛍️ ' + (lang.value.product_inquiry || 'Product inquiry');
    return conv.latest_message.body;
};

const formatTime = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const today = new Date();
    if (d.toDateString() === today.toDateString()) {
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    return d.toLocaleDateString();
};
</script>

<template>
    <div class="h-full overflow-y-auto bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ lang.title || 'Messages' }}</h2>
        </div>

        <div v-if="conversations.length === 0" class="p-8 text-center text-sm text-gray-500">
            {{ lang.no_conversations || 'No conversations yet.' }}
        </div>

        <Link
            v-for="conv in conversations"
            :key="conv.id"
            :href="route('messages.show', conv.id)"
            preserve-scroll
            :aria-current="activeId === conv.id ? 'page' : undefined"
            :class="[
                'flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition',
                activeId === conv.id ? 'bg-indigo-50 dark:bg-indigo-900/20' : ''
            ]"
        >
            <img
                :src="conv.other_user.profile_photo_url"
                :alt="conv.other_user.name"
                loading="lazy"
                class="w-12 h-12 rounded-full object-cover flex-shrink-0"
            />
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                        {{ conv.other_user.name }}
                    </p>
                    <span class="text-[11px] text-gray-400 flex-shrink-0">{{ formatTime(conv.last_message_at) }}</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                    <span class="text-gray-400">[{{ conv.order ? conv.order.order_number : conv.shop_name }}]</span> {{ previewText(conv) }}
                </p>
            </div>
            <span
                v-if="conv.unread_count > 0"
                :aria-label="`${conv.unread_count} ${lang.unread_label || 'unread'}`"
                class="flex-shrink-0 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full min-w-[20px]"
            >
                {{ conv.unread_count > 99 ? '99+' : conv.unread_count }}
            </span>
        </Link>
    </div>
</template>
