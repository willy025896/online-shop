<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import RowActions from '@/Components/RowActions.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { useAsyncActionGroup } from '@/Composables/useAsyncAction';

defineProps({
    coupons: Object,
});

const page = usePage();
const c = computed(() => page.props.lang?.coupons || {});

const formatValue = (coupon) =>
    coupon.type === 'percentage' ? `${Number(coupon.value)}%` : `$${Number(coupon.value).toFixed(2)}`;

const usageText = (coupon) =>
    coupon.usage_limit === null ? `${coupon.used_count}` : `${coupon.used_count} / ${coupon.usage_limit}`;

const { isProcessing: isDeleting, run } = useAsyncActionGroup();

const couponPendingDelete = ref(null);
const confirmDeleteCoupon = (coupon) => {
    couponPendingDelete.value = coupon;
};

const deleteCoupon = () => {
    const coupon = couponPendingDelete.value;
    run(coupon.id, (finish) => router.delete(route('seller.coupons.destroy', coupon.id), {
        onSuccess: () => { couponPendingDelete.value = null; },
        onFinish: finish,
    }));
};
</script>

<template>
    <SellerLayout :title="c.title">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ c.title }}</h2>
                <Link :href="route('seller.coupons.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    {{ c.add }}
                </Link>
            </div>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table v-if="coupons.data.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ c.code }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ c.discount }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ c.min_spend }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ c.used }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ c.status }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ c.actions }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="coupon in coupons.data" :key="coupon.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ coupon.code }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ formatValue(coupon) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${{ Number(coupon.min_spend).toFixed(2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ usageText(coupon) }}</td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                coupon.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ]">{{ coupon.is_active ? c.active : c.inactive }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <RowActions :loading="isDeleting(coupon.id)">
                                <Link :href="route('seller.coupons.edit', coupon.id)" class="text-indigo-600 hover:text-indigo-900">{{ c.action_edit }}</Link>
                                <button @click="confirmDeleteCoupon(coupon)" class="text-red-600 hover:text-red-900">{{ c.action_delete }}</button>
                            </RowActions>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-gray-500">
                {{ c.no_coupons }} <Link :href="route('seller.coupons.create')" class="text-indigo-600 hover:underline">{{ c.create_first }}</Link>.
            </div>
        </div>

        <div class="mt-6">
            <Pagination :links="coupons.links" />
        </div>

        <ConfirmationModal :show="couponPendingDelete !== null" @close="couponPendingDelete = null">
            <template #title>{{ c.action_delete }}</template>
            <template #content>
                {{ (c.delete_confirm || 'Delete ":code"?').replace(':code', couponPendingDelete?.code) }}
            </template>
            <template #footer>
                <SecondaryButton @click="couponPendingDelete = null">{{ c.cancel }}</SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': couponPendingDelete && isDeleting(couponPendingDelete.id) }"
                    :disabled="couponPendingDelete && isDeleting(couponPendingDelete.id)"
                    @click="deleteCoupon"
                >
                    {{ c.confirm }}
                </DangerButton>
            </template>
        </ConfirmationModal>
    </SellerLayout>
</template>
