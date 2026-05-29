<template>
    <div class="space-y-1.5">
        <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2 text-sm">
            <span class="w-5 text-right text-gray-600">{{ star }}</span>
            <svg class="w-4 h-4 text-yellow-400 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                <div
                    class="bg-yellow-400 h-2 rounded-full transition-all duration-500"
                    :style="{ width: percentage(star) + '%' }"
                />
            </div>
            <span class="w-8 text-right text-gray-500">{{ distribution[star] ?? 0 }}</span>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    distribution: { type: Object, required: true }, // { 5: 10, 4: 5, ... }
    total: { type: Number, required: true },
})

function percentage(star) {
    if (props.total === 0) return 0
    return Math.round(((props.distribution[star] ?? 0) / props.total) * 100)
}
</script>
