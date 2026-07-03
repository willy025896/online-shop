<script setup>
import { ref } from 'vue';
import ImageWithFallback from '@/Components/ImageWithFallback.vue';

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
    productName: {
        type: String,
        default: '',
    },
});

const selectedIndex = ref(0);
</script>

<template>
    <div>
        <div class="aspect-square bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden mb-4">
            <ImageWithFallback
                :src="images.length ? `/storage/${images[selectedIndex].path}` : null"
                :alt="productName"
                icon-class="h-20 w-20"
                class="w-full h-full object-cover"
            />
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
                <ImageWithFallback :src="`/storage/${image.path}`" :alt="`${productName} ${index + 1}`" icon-class="h-6 w-6" loading="lazy" class="w-full h-full object-cover" />
            </button>
        </div>
    </div>
</template>
