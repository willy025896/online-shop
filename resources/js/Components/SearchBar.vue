<script setup>
import { ref, watch, onBeforeUnmount, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import SearchSuggestionsDropdown from '@/Components/SearchSuggestionsDropdown.vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

const search = ref(props.modelValue);
const suggestions = ref([]);
const hotQueries = ref([]);
const historyQueries = ref([]);
const showDropdown = ref(false);
const isLoading = ref(false);
let debounceTimer = null;
const suggestionsUrl = typeof route !== 'undefined' ? route('search.suggestions') : '/search/suggestions';
const historyKey = 'search_history';
const maxHistoryItems = 7;

const loadHistory = () => {
    try {
        const stored = localStorage.getItem(historyKey);
        if (!stored) {
            return;
        }

        const parsed = JSON.parse(stored);
        if (Array.isArray(parsed)) {
            historyQueries.value = parsed.filter((item) => typeof item === 'string');
        }
    } catch {
        historyQueries.value = [];
    }
};

const saveHistory = () => {
    localStorage.setItem(historyKey, JSON.stringify(historyQueries.value));
};

const addHistoryQuery = (query) => {
    const normalized = query.trim();
    if (!normalized) {
        return;
    }

    historyQueries.value = historyQueries.value.filter((item) => item.toLowerCase() !== normalized.toLowerCase());
    historyQueries.value.unshift(normalized);
    historyQueries.value = historyQueries.value.slice(0, maxHistoryItems);
    saveHistory();
};

const fetchSuggestions = async () => {
    const query = search.value.trim();
    isLoading.value = true;

    try {
        const response = await fetch(`${suggestionsUrl}?q=${encodeURIComponent(query)}`);
        if (!response.ok) {
            return;
        }

        const data = await response.json();
        suggestions.value = data.products || [];
        hotQueries.value = data.hot_queries || [];
        showDropdown.value = true;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

const scheduleFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchSuggestions();
    }, 250);
};

watch(search, (value, oldValue) => {
    if (value === oldValue) {
        return;
    }

    scheduleFetch();
});

onMounted(() => {
    loadHistory();
});

onBeforeUnmount(() => {
    clearTimeout(debounceTimer);
});

const submit = () => {
    const query = search.value.trim();
    if (!query) {
        router.get(route('products.index'), {
            preserveState: true,
        });
        showDropdown.value = false;
        return;
    }

    addHistoryQuery(query);
    router.get(route('products.index'), { search: query }, {
        preserveState: true,
    });
    showDropdown.value = false;
};

const selectProduct = (product) => {
    router.visit(route('products.show', product.slug));
    showDropdown.value = false;
};

const selectQuery = (query) => {
    search.value = query;
    addHistoryQuery(query);
    submit();
};

const onFocus = () => {
    if (!search.value.trim()) {
        showDropdown.value = true;
        if (!hotQueries.value.length) {
            fetchSuggestions();
        }
        return;
    }

    fetchSuggestions();
};

const onBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
    }, 150);
};
</script>

<template>
    <div class="relative" @keydown.escape="showDropdown = false">
        <form @submit.prevent="submit" class="flex">
            <input
                v-model="search"
                @focus="onFocus"
                @blur="onBlur"
                type="text"
                placeholder="Search products..."
                class="flex-1 rounded-l-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                aria-label="Search products"
                role="combobox"
                aria-haspopup="listbox"
                :aria-expanded="showDropdown"
                aria-autocomplete="list"
            />
            <button type="submit" class="px-4 bg-indigo-600 text-white rounded-r-lg hover:bg-indigo-700 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </form>

        <SearchSuggestionsDropdown
            :query="search"
            :suggestions="suggestions"
            :hot-queries="hotQueries"
            :history-queries="historyQueries"
            :visible="showDropdown"
            :loading="isLoading"
            @select-product="selectProduct"
            @select-query="selectQuery"
        />
    </div>
</template>
