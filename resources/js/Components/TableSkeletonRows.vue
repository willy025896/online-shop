<script setup>
import Skeleton from '@/Components/Skeleton.vue';

defineProps({
    columns: { type: Number, required: true },
    rows: { type: Number, default: 5 },
});
</script>

<template>
    <tr v-for="row in rows" :key="row" aria-hidden="true">
        <td v-for="col in columns" :key="col" class="px-6 py-4">
            <Skeleton height="0.875rem" :width="col === 1 ? '80%' : '60%'" />
        </td>
    </tr>
</template>
