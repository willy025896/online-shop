<script setup>
import { computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import Spinner from '@/Components/Spinner.vue';
import { useAsyncAction } from '@/Composables/useAsyncAction';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    productId: {
        type: Number,
        required: true,
    },
    size: {
        type: String,
        default: 'sm', // 'sm' | 'md'
    },
});

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);
const isFavorited = computed(() =>
    (page.props.wishlistProductIds || []).includes(props.productId)
);

const { processing, run } = useAsyncAction();
const toast = useToast();

const toggle = (e) => {
    e.preventDefault();
    e.stopPropagation();

    run((finish) => router.post(
        route('wishlist.toggle'),
        { product_id: props.productId },
        {
            preserveScroll: true,
            only: ['wishlistProductIds', 'flash'],
            onError: (errors) => toast.error(errors.product_id),
            onFinish: finish,
        }
    ));
};
</script>

<template>
    <Link
        v-if="!isLoggedIn"
        :href="route('login')"
        class="inline-flex items-center justify-center rounded-full transition"
        :class="size === 'md' ? 'p-2' : 'p-1.5'"
        @click.stop
        :title="'Add to wishlist'"
    >
        <svg :class="size === 'md' ? 'h-6 w-6' : 'h-5 w-5'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="text-gray-400 hover:text-red-500 transition">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
        </svg>
    </Link>

    <button
        v-else
        type="button"
        class="inline-flex items-center justify-center rounded-full transition disabled:opacity-50"
        :class="size === 'md' ? 'p-2' : 'p-1.5'"
        :disabled="processing"
        @click="toggle"
        :title="isFavorited ? 'Remove from wishlist' : 'Add to wishlist'"
    >
        <Spinner v-if="processing" :class="size === 'md' ? 'h-6 w-6 text-gray-400' : 'h-5 w-5 text-gray-400'" />
        <svg
            v-else
            :class="[size === 'md' ? 'h-6 w-6' : 'h-5 w-5', isFavorited ? 'text-red-500' : 'text-gray-400 hover:text-red-500']"
            :fill="isFavorited ? 'currentColor' : 'none'"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            class="transition"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
        </svg>
    </button>
</template>
