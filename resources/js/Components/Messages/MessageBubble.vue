<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ImageWithFallback from '@/Components/ImageWithFallback.vue';

const props = defineProps({
    message: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
const isSelf = computed(() => props.message.sender_id === page.props.auth.user.id);

const time = computed(() => {
    const d = new Date(props.message.created_at);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
});
</script>

<template>
    <div :class="['flex gap-2 mb-3', isSelf ? 'flex-row-reverse' : 'flex-row']">
        <img
            :src="message.sender.profile_photo_url"
            :alt="message.sender.name"
            loading="lazy"
            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
        />
        <div :class="['max-w-[70%] flex flex-col', isSelf ? 'items-end' : 'items-start']">
            <div
                :class="[
                    'rounded-2xl px-4 py-2 break-words',
                    isSelf
                        ? 'bg-indigo-600 text-white rounded-br-sm'
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-bl-sm',
                ]"
            >
                <ImageWithFallback
                    v-if="message.image_path"
                    :src="`/storage/${message.image_path}`"
                    :alt="lang.image_label || 'Image'"
                    icon-class="h-8 w-8"
                    loading="lazy"
                    class="rounded-lg max-w-full max-h-64 object-cover mb-1"
                    @click.stop
                />
                <p v-if="message.body" class="whitespace-pre-wrap text-sm">{{ message.body }}</p>
            </div>
            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-gray-500 px-1">
                <span>{{ time }}</span>
                <span v-if="isSelf && message.read_at" class="text-indigo-500">✓ Read</span>
            </div>
        </div>
    </div>
</template>
