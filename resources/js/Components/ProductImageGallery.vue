<script setup>
import { ref } from 'vue';

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
});

const selectedIndex = ref(0);
</script>

<template>
    <div>
        <div class="aspect-square bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden mb-4">
            <img
                v-if="images.length"
                :src="`/storage/${images[selectedIndex].path}`"
                class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <svg class="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>
        <div v-if="images.length > 1" class="grid grid-cols-5 gap-2">
            <button
                v-for="(image, index) in images"
                :key="image.id"
                @click="selectedIndex = index"
                :class="[
                    'aspect-square rounded-md overflow-hidden border-2',
                    index === selectedIndex ? 'border-indigo-500' : 'border-transparent'
                ]"
            >
                <img :src="`/storage/${image.path}`" class="w-full h-full object-cover" />
            </button>
        </div>
    </div>
</template>
