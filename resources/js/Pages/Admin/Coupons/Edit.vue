<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CouponForm from './Partials/CouponForm.vue';

const props = defineProps({
    coupon: Object,
});

const page = usePage();
const c = computed(() => page.props.lang?.coupons || {});

const toDate = (v) => (v ? String(v).substring(0, 10) : '');

const form = useForm({
    code: props.coupon.code,
    type: props.coupon.type,
    value: props.coupon.value,
    min_spend: props.coupon.min_spend,
    max_discount: props.coupon.max_discount ?? '',
    usage_limit: props.coupon.usage_limit ?? '',
    per_user_limit: props.coupon.per_user_limit ?? '',
    starts_at: toDate(props.coupon.starts_at),
    expires_at: toDate(props.coupon.expires_at),
    is_active: props.coupon.is_active,
});

const submit = () => form.put(route('admin.coupons.update', props.coupon.id));
</script>

<template>
    <AdminLayout :title="c.edit">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ c.edit }}</h2>
        </template>

        <CouponForm :form="form" :submit-label="c.update" @submit="submit" />
    </AdminLayout>
</template>
