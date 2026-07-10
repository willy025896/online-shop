<script setup>
const props = defineProps({
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

function toggle(star) {
    emit('update:modelValue', props.modelValue == star ? null : star);
}
</script>

<template>
    <div class="flex gap-1">
        <button
            v-for="star in [4, 3]"
            :key="star"
            type="button"
            :class="[
                'px-2 py-1 rounded-full text-xs font-medium border transition',
                modelValue == star
                    ? 'bg-yellow-400 border-yellow-400 text-white'
                    : 'border-gray-300 text-gray-600 hover:border-yellow-400'
            ]"
            @click="toggle(star)"
        >
            {{ star }}★+
        </button>
    </div>
</template>
