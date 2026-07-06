<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ImageWithFallback from '@/Components/ImageWithFallback.vue';

const props = defineProps({
    product: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
</script>

<template>
    <Link
        v-if="product"
        :href="route('products.show', product.slug)"
        class="flex items-center gap-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl p-2 hover:border-indigo-400 transition max-w-full"
    >
        <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
            <ImageWithFallback
                :src="product.thumbnail ? `/storage/${product.thumbnail}` : null"
                :alt="product.name"
                icon-class="h-6 w-6"
                loading="lazy"
                class="w-full h-full object-cover"
            />
        </div>
        <div class="min-w-0 flex-1 text-left">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ product.name }}</p>
            <p class="text-sm font-bold text-red-600 mt-0.5">${{ product.price }}</p>
        </div>
    </Link>
    <div v-else class="text-xs text-gray-400 italic px-2 py-1">
        {{ lang.product_unavailable || 'This product is no longer available' }}
    </div>
</template>
