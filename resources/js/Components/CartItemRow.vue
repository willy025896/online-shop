<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Spinner from '@/Components/Spinner.vue';
import ImageWithFallback from '@/Components/ImageWithFallback.vue';
import { useAsyncAction } from '@/Composables/useAsyncAction';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    item: Object,
    checked: Boolean,
});

const emit = defineEmits(['toggle']);

const variantLabel = computed(() => {
    if (!props.item.variant?.option_values?.length) return null;
    return props.item.variant.option_values.map((ov) => `${ov.option.name}: ${ov.value}`).join(' / ');
});

const quantity = ref(props.item.quantity);
const toast = useToast();
const { processing: updatingQuantity, run: runUpdate } = useAsyncAction();
const { processing: removing, run: runRemove } = useAsyncAction();

watch(() => props.item.quantity, (newQuantity) => {
    quantity.value = newQuantity;
});

const updateQuantity = () => {
    runUpdate((finish) => router.patch(route('cart.update', props.item.id), {
        quantity: quantity.value,
    }, {
        preserveScroll: true,
        onError: (errors) => toast.error(errors.quantity),
        onFinish: finish,
    }));
};

const removeItem = () => {
    runRemove((finish) => router.delete(route('cart.destroy', props.item.id), {
        preserveScroll: true,
        onFinish: finish,
    }));
};
</script>

<template>
    <div class="flex items-center gap-4 py-4 border-b border-gray-200 dark:border-gray-700">
        <input
            type="checkbox"
            :checked="checked"
            @change="emit('toggle', item.id)"
            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer flex-shrink-0"
        />
        <div class="h-20 w-20 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded-md overflow-hidden">
            <ImageWithFallback
                :src="item.product?.primary_image ? `/storage/${item.product.primary_image.path}` : null"
                :alt="item.product?.name"
                icon-class="h-8 w-8"
                loading="lazy"
                class="w-full h-full object-cover"
            />
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ item.product?.name }}</h4>
            <p v-if="variantLabel" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ variantLabel }}</p>
            <p class="text-sm font-semibold text-red-600 mt-1">${{ item.unit_price }}</p>
        </div>
        <div class="flex items-center gap-2">
            <select
                v-model="quantity"
                @change="updateQuantity"
                :disabled="updatingQuantity"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm disabled:opacity-50"
            >
                <option v-for="n in 99" :key="n" :value="n">{{ n }}</option>
            </select>
            <Spinner v-if="updatingQuantity" class="h-4 w-4 text-gray-400" />
        </div>
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 w-20 text-right">
            ${{ (item.quantity * item.unit_price).toFixed(2) }}
        </div>
        <button
            @click="removeItem"
            :disabled="removing"
            aria-label="Remove item"
            class="text-gray-400 hover:text-red-500 disabled:opacity-50"
        >
            <Spinner v-if="removing" class="h-5 w-5" />
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </div>
</template>
