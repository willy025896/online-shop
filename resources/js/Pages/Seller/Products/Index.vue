<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import RowActions from '@/Components/RowActions.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { useDeleteConfirmation } from '@/Composables/useDeleteConfirmation';

const props = defineProps({
    products: Object,
    filters: { type: Object, default: () => ({}) },
    lowStockThreshold: { type: Number, default: 0 },
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const toggleLowStock = () => {
    router.get(
        route('seller.products.index'),
        props.filters.low_stock ? {} : { low_stock: 1 },
        { preserveScroll: true, preserveState: true },
    );
};

const {
    pending: productPendingDelete,
    label: productPendingDeleteName,
    isDeleting,
    confirm: confirmDeleteProduct,
    cancel: cancelDeleteProduct,
    execute: deleteProduct,
} = useDeleteConfirmation('seller.products.destroy');
</script>

<template>
    <SellerLayout :title="lang.products?.title">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.products?.title }}</h2>
                <div class="flex items-center gap-2">
                    <button
                        @click="toggleLowStock"
                        :class="[
                            'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border transition-colors',
                            filters.low_stock
                                ? 'bg-amber-100 border-amber-300 text-amber-800'
                                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                        ]"
                    >
                        {{ lang.products?.low_stock_filter }}
                    </button>
                    <Link :href="route('seller.products.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        {{ lang.products?.add }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-x-auto">
            <table v-if="products.data.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.name }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.category }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.price }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.stock }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.status }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.products?.actions }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="product in products.data" :key="product.id">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 rounded bg-gray-200 dark:bg-gray-600 overflow-hidden">
                                    <img v-if="product.primary_image" :src="`/storage/${product.primary_image.path}`" :alt="product.name" loading="lazy" class="h-full w-full object-cover" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ product.name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ product.category?.name || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">${{ product.price }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span
                                v-if="product.stock <= lowStockThreshold"
                                :class="[
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    product.stock === 0 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'
                                ]"
                            >{{ product.stock }}</span>
                            <span v-else class="text-gray-900 dark:text-gray-100">{{ product.stock }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                product.status === 'active' ? 'bg-green-100 text-green-800' :
                                product.status === 'draft' ? 'bg-gray-100 text-gray-800' :
                                'bg-red-100 text-red-800'
                            ]">{{ lang.products?.[product.status] || product.status }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <RowActions :loading="isDeleting(product.id)">
                                <Link :href="route('seller.products.edit', product.id)" class="text-indigo-600 hover:text-indigo-900">{{ lang.products?.action_edit }}</Link>
                                <button @click="confirmDeleteProduct(product)" class="text-red-600 hover:text-red-900">{{ lang.products?.action_delete }}</button>
                            </RowActions>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-gray-500">
                {{ lang.products?.no_products }} <Link :href="route('seller.products.create')" class="text-indigo-600 hover:underline">{{ lang.products?.create_first }}</Link>.
            </div>
        </div>

        <div class="mt-6">
            <Pagination :links="products.links" />
        </div>

        <ConfirmationModal :show="productPendingDelete !== null" @close="cancelDeleteProduct">
            <template #title>{{ lang.products?.action_delete }}</template>
            <template #content>
                {{ (lang.products?.delete_confirm || 'Are you sure you want to delete ":name"?').replace(':name', productPendingDeleteName) }}
            </template>
            <template #footer>
                <SecondaryButton @click="cancelDeleteProduct">{{ lang.products?.cancel }}</SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': productPendingDelete && isDeleting(productPendingDelete.id) }"
                    :disabled="productPendingDelete && isDeleting(productPendingDelete.id)"
                    @click="deleteProduct"
                >
                    {{ lang.products?.confirm }}
                </DangerButton>
            </template>
        </ConfirmationModal>
    </SellerLayout>
</template>
