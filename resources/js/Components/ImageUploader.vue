<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
    images: {
        type: Array,
        default: () => [],
    },
});

const fileInput = ref(null);
const dragOver = ref(false);

const uploadImages = (files) => {
    const formData = new FormData();
    Array.from(files).forEach((file) => {
        formData.append('images[]', file);
    });

    router.post(route('seller.products.images.store', props.product.id), formData, {
        preserveScroll: true,
    });
};

const onFileChange = (e) => {
    uploadImages(e.target.files);
    fileInput.value.value = '';
};

const onDrop = (e) => {
    dragOver.value = false;
    uploadImages(e.dataTransfer.files);
};

const deleteImage = (imageId) => {
    router.delete(route('seller.products.images.destroy', imageId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div>
        <div class="grid grid-cols-5 gap-3 mb-4">
            <div v-for="image in images" :key="image.id" class="relative aspect-square rounded-lg overflow-hidden group">
                <img :src="`/storage/${image.path}`" class="w-full h-full object-cover" />
                <button
                    @click="deleteImage(image.id)"
                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div
            v-if="images.length < 5"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="onDrop"
            :class="[
                'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition',
                dragOver ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'
            ]"
            @click="fileInput.click()"
        >
            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Drop images here or click to upload</p>
            <p class="text-xs text-gray-400 mt-1">Max 5 images, 2MB each (JPEG, PNG, WebP)</p>
            <input ref="fileInput" type="file" multiple accept="image/*" class="hidden" @change="onFileChange" />
        </div>
    </div>
</template>
