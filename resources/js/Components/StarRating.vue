<template>
    <div class="flex items-center gap-0.5" :class="sizeClass">
        <button
            v-for="star in 5"
            :key="star"
            type="button"
            :disabled="readonly"
            :class="[
                'transition-colors',
                readonly ? 'cursor-default' : 'cursor-pointer hover:scale-110',
                star <= (hovered ?? modelValue) ? 'text-yellow-400' : 'text-gray-300',
            ]"
            @click="!readonly && emit('update:modelValue', star)"
            @mouseenter="!readonly && (hovered = star)"
            @mouseleave="!readonly && (hovered = null)"
        >
            <svg :class="starSizeClass" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
        </button>
        <span v-if="showCount && count !== undefined" class="ml-1 text-gray-500" :class="countSizeClass">
            ({{ count }})
        </span>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    modelValue: { type: Number, default: 0 },
    readonly: { type: Boolean, default: false },
    size: { type: String, default: 'md' }, // sm | md | lg
    showCount: { type: Boolean, default: false },
    count: { type: Number, default: undefined },
})

const emit = defineEmits(['update:modelValue'])
const hovered = ref(null)

const sizeClass = computed(() => ({ sm: 'text-sm', md: 'text-base', lg: 'text-lg' }[props.size] ?? 'text-base'))
const starSizeClass = computed(() => ({ sm: 'w-4 h-4', md: 'w-5 h-5', lg: 'w-6 h-6' }[props.size] ?? 'w-5 h-5'))
const countSizeClass = computed(() => ({ sm: 'text-xs', md: 'text-sm', lg: 'text-base' }[props.size] ?? 'text-sm'))
</script>
