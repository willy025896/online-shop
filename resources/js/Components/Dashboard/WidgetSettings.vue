<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    widgets: { type: Object, required: true },
    lang: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['save']);

const open = ref(false);
const local = ref({ ...props.widgets });

watch(() => props.widgets, (v) => { local.value = { ...v }; }, { deep: true });

const widgetKeys = [
    { key: 'revenue', labelKey: 'widget_revenue' },
    { key: 'order_status', labelKey: 'widget_order_status' },
    { key: 'top_products', labelKey: 'widget_top_products' },
    { key: 'revenue_chart', labelKey: 'widget_revenue_chart' },
    { key: 'low_stock', labelKey: 'widget_low_stock' },
];

const save = () => {
    emit('save', { ...local.value });
    open.value = false;
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-40">
        <button
            @click="open = !open"
            class="bg-brand-500 hover:bg-brand-600 text-white rounded-full p-3 shadow-lift focus:outline-none focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
            :title="lang.widget_settings"
            :aria-label="lang.widget_settings"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="open" class="absolute bottom-14 right-0 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-100 dark:border-gray-700 p-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">{{ lang.widget_settings }}</p>
                <div class="space-y-2">
                    <label
                        v-for="w in widgetKeys"
                        :key="w.key"
                        class="flex items-center gap-2 cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            v-model="local[w.key]"
                            class="rounded border-gray-300 text-brand-500 focus:ring-accent-400"
                        />
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ lang[w.labelKey] ?? w.key }}</span>
                    </label>
                </div>
                <button
                    @click="save"
                    class="mt-4 w-full bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium py-1.5 rounded-md"
                >
                    {{ lang.save_preferences }}
                </button>
            </div>
        </Transition>
    </div>
</template>
