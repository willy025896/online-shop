<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CartItemRow from '@/Components/CartItemRow.vue';
import CartSummary from '@/Components/CartSummary.vue';

const props = defineProps({
    cart: Object,
    totals: Object,
    shippingConfig: Object,
});

// Per-shop shipping estimate, mirroring the server's ShippingService rule.
const shippingFeeFor = (shopSubtotal) => {
    const threshold = props.shippingConfig?.free_threshold;
    if (threshold != null && shopSubtotal >= threshold) return 0;
    return Number(props.shippingConfig?.flat_fee ?? 0);
};

const page = usePage();
const lang = computed(() => page.props.lang || {});

const allItems = computed(() => props.cart?.items ?? []);

const groupedItems = computed(() => {
    const map = new Map();
    for (const item of allItems.value) {
        const shopId = item.product?.shop?.id ?? 0;
        if (!map.has(shopId)) {
            map.set(shopId, { shopId, shopName: item.product?.shop?.name ?? '', items: [] });
        }
        map.get(shopId).items.push(item);
    }
    return [...map.values()];
});

// Selection — initialised to all IDs selected
const selectedIds = ref(allItems.value.map(i => i.id));

const isSelected = (id) => selectedIds.value.includes(id);

const toggleItem = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
};

const isShopAllSelected = (items) => items.every(i => isSelected(i.id));
const isShopSomeSelected = (items) => items.some(i => isSelected(i.id));

const toggleShop = (items) => {
    if (isShopAllSelected(items)) {
        for (const item of items) {
            const idx = selectedIds.value.indexOf(item.id);
            if (idx >= 0) selectedIds.value.splice(idx, 1);
        }
    } else {
        for (const item of items) {
            if (!isSelected(item.id)) selectedIds.value.push(item.id);
        }
    }
};

const isAllSelected = computed(() =>
    allItems.value.length > 0 && allItems.value.every(i => isSelected(i.id))
);
const isGlobalIndeterminate = computed(() =>
    allItems.value.some(i => isSelected(i.id)) && !isAllSelected.value
);

const toggleAll = () => {
    if (isAllSelected.value) selectedIds.value = [];
    else selectedIds.value = allItems.value.map(i => i.id);
};

const selectedCount = computed(() => selectedIds.value.length);

const selectedTotals = computed(() => {
    const selected = allItems.value.filter(i => isSelected(i.id));
    const subtotal = selected.reduce((sum, i) => sum + i.quantity * parseFloat(i.unit_price), 0);

    // Group selected items by shop, then sum each shop's shipping fee — the
    // checkout will split these into one order per shop.
    const subtotalByShop = new Map();
    for (const i of selected) {
        const shopId = i.product?.shop?.id ?? 0;
        subtotalByShop.set(shopId, (subtotalByShop.get(shopId) ?? 0) + i.quantity * parseFloat(i.unit_price));
    }
    let shipping_fee = 0;
    for (const shopSubtotal of subtotalByShop.values()) {
        shipping_fee += shippingFeeFor(shopSubtotal);
    }

    return { subtotal, shipping_fee, total: subtotal + shipping_fee };
});

const checkoutSelected = () => {
    router.get(route('checkout.index'), { item_ids: selectedIds.value });
};

// Custom directive for indeterminate checkbox state
const vIndeterminate = {
    mounted: (el, { value }) => { el.indeterminate = value; },
    updated: (el, { value }) => { el.indeterminate = value; },
};
</script>

<template>
    <AppLayout :title="lang.title">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ lang.title }}</h1>

            <div v-if="allItems.length" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <!-- Global select-all header -->
                    <div class="flex items-center gap-3 px-1 pb-3 border-b border-gray-200 dark:border-gray-700 mb-2">
                        <input
                            v-indeterminate="isGlobalIndeterminate"
                            type="checkbox"
                            :checked="isAllSelected"
                            @change="toggleAll"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                        />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ lang.select_all }}</span>
                    </div>

                    <!-- Groups by shop -->
                    <div v-for="group in groupedItems" :key="group.shopId" class="mb-6">
                        <!-- Shop header -->
                        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-t-lg border border-gray-200 dark:border-gray-700">
                            <input
                                type="checkbox"
                                :checked="isShopAllSelected(group.items)"
                                :indeterminate="isShopSomeSelected(group.items) && !isShopAllSelected(group.items)"
                                @change="toggleShop(group.items)"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                            />
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ group.shopName }}</span>
                        </div>

                        <!-- Items -->
                        <div class="border-x border-b border-gray-200 dark:border-gray-700 rounded-b-lg px-3">
                            <CartItemRow
                                v-for="item in group.items"
                                :key="item.id"
                                :item="item"
                                :checked="isSelected(item.id)"
                                @toggle="toggleItem"
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <CartSummary :totals="selectedTotals">
                        <button
                            :disabled="selectedCount === 0"
                            @click="checkoutSelected"
                            class="mt-4 block w-full text-center bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            {{ lang.checkout_selected?.replace(':count', selectedCount) }}
                        </button>
                    </CartSummary>
                </div>
            </div>

            <div v-else class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                </svg>
                <h2 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ lang.empty }}</h2>
                <a :href="route('products.index')" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">
                    {{ lang.continue_shopping }}
                </a>
            </div>
        </div>
    </AppLayout>
</template>
