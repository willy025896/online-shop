<script setup>
const props = defineProps({
    query: {
        type: String,
        default: '',
    },
    suggestions: {
        type: Array,
        default: () => [],
    },
    hotQueries: {
        type: Array,
        default: () => [],
    },
    historyQueries: {
        type: Array,
        default: () => [],
    },
    visible: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select-product', 'select-query']);

const selectProduct = (product) => {
    emit('select-product', product);
};

const selectQuery = (query) => {
    emit('select-query', query);
};
</script>

<template>
    <transition name="fade">
        <div
            v-if="visible"
            class="absolute z-20 mt-2 w-full rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 shadow-lg overflow-hidden max-h-96 overflow-y-auto"
        >
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                <div class="p-2" v-if="loading">
                    <div class="py-4 text-center text-sm text-gray-500">搜尋中...</div>
                </div>

                <div class="p-2" v-else>
                    <div v-if="props.query && props.suggestions.length">
                        <div class="text-xs uppercase tracking-wider text-gray-500 px-3 pb-2">建議商品</div>
                        <div class="space-y-1">
                            <button
                                v-for="product in props.suggestions"
                                :key="product.id"
                                @mousedown.prevent="selectProduct(product)"
                                type="button"
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                            >
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ product.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ product.shop_name }}</div>
                            </button>
                        </div>
                    </div>

                    <div v-if="!props.query && props.historyQueries.length" class="pt-2">
                        <div class="text-xs uppercase tracking-wider text-gray-500 px-3 pb-2">搜尋歷史</div>
                        <div class="flex flex-wrap gap-2 px-3 pb-3">
                            <button
                                v-for="item in props.historyQueries"
                                :key="item"
                                @mousedown.prevent="selectQuery(item)"
                                type="button"
                                class="rounded-full border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:text-gray-200 dark:hover:border-gray-500"
                            >
                                {{ item }}
                            </button>
                        </div>
                    </div>

                    <div v-if="props.hotQueries.length" class="pt-2">
                        <div class="text-xs uppercase tracking-wider text-gray-500 px-3 pb-2">熱門搜尋</div>
                        <div class="flex flex-wrap gap-2 px-3 pb-3">
                            <button
                                v-for="item in props.hotQueries"
                                :key="item"
                                @mousedown.prevent="selectQuery(item)"
                                type="button"
                                class="rounded-full border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:text-gray-200 dark:hover:border-gray-500"
                            >
                                {{ item }}
                            </button>
                        </div>
                    </div>

                    <div v-if="props.query && !props.suggestions.length && !props.loading" class="px-3 py-4 text-sm text-gray-500">
                        沒有符合的商品建議，按 Enter 搜尋其他關鍵字。
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
