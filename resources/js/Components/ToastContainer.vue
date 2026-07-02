<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const page = usePage();
const { toasts, success, error, dismiss } = useToast();

watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([newSuccess, newError]) => {
        if (newSuccess) success(newSuccess);
        if (newError) error(newError);
    },
    { immediate: true }
);
</script>

<template>
    <Teleport to="body">
        <div
            role="status"
            aria-live="polite"
            class="fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end sm:bottom-4 sm:right-4 sm:left-auto"
        >
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-2 sm:translate-x-4 sm:translate-y-0"
                enter-to-class="opacity-100 translate-y-0 sm:translate-x-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="flex w-full max-w-sm items-start gap-3 rounded-lg p-4 shadow-lg sm:w-96"
                    :class="toast.type === 'success'
                        ? 'bg-indigo-600 dark:bg-indigo-500'
                        : 'bg-red-600 dark:bg-red-500'"
                >
                    <svg v-if="toast.type === 'success'" class="h-5 w-5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="h-5 w-5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>

                    <p class="flex-1 text-sm font-medium text-white">{{ toast.message }}</p>

                    <button
                        type="button"
                        class="shrink-0 rounded-md p-1 text-white/80 hover:text-white focus:outline-none"
                        aria-label="Dismiss"
                        @click="dismiss(toast.id)"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
