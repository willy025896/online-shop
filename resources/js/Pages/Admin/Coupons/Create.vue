<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CouponForm from './Partials/CouponForm.vue';

const page = usePage();
const c = computed(() => page.props.lang?.coupons || {});

const form = useForm({
    code: '',
    type: 'percentage',
    value: '',
    min_spend: 0,
    max_discount: '',
    usage_limit: '',
    per_user_limit: '',
    starts_at: '',
    expires_at: '',
    is_active: true,
});

const submit = () => form.post(route('admin.coupons.store'));
</script>

<template>
    <AdminLayout :title="c.create">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ c.create }}</h2>
        </template>

        <CouponForm :form="form" :submit-label="c.create" @submit="submit" />
    </AdminLayout>
</template>
