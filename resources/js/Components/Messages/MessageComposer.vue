<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    conversationId: Number,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const form = useForm({
    body: '',
    image: null,
});

const fileInput = ref(null);
const previewUrl = ref(null);

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    previewUrl.value = URL.createObjectURL(file);
};

const removeImage = () => {
    form.image = null;
    previewUrl.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const submit = () => {
    if (!form.body.trim() && !form.image) return;

    form.post(route('messages.store', props.conversationId), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body', 'image');
            previewUrl.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="border-t border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800">
        <div v-if="previewUrl" class="mb-2 relative inline-block">
            <img :src="previewUrl" :alt="lang.image_label || 'Image'" class="h-20 w-20 object-cover rounded-lg" />
            <button
                type="button"
                @click="removeImage"
                aria-label="Remove image"
                class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-gray-700 text-white text-xs flex items-center justify-center"
            >×</button>
        </div>
        <div class="flex items-end gap-2">
            <label class="cursor-pointer p-2 text-gray-500 hover:text-indigo-600 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/jpg,image/webp" @change="onFileChange" class="hidden" />
            </label>
            <textarea
                v-model="form.body"
                :placeholder="lang.input_placeholder || 'Type a message...'"
                rows="1"
                @keydown.enter.exact.prevent="submit"
                class="flex-1 resize-none rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            ></textarea>
            <button
                type="submit"
                :disabled="form.processing || (!form.body.trim() && !form.image)"
                aria-label="Send message"
                class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white rounded-full p-2 transition"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </button>
        </div>
        <div v-if="form.errors.body" class="text-xs text-red-500 mt-1">{{ form.errors.body }}</div>
        <div v-if="form.errors.image" class="text-xs text-red-500 mt-1">{{ form.errors.image }}</div>
    </form>
</template>
