<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AddressForm from './Partials/AddressForm.vue';

const page = usePage();
const a = computed(() => page.props.lang || {});

const form = useForm({
    label: '',
    recipient_name: '',
    phone: '',
    address: '',
    is_default: false,
});

const submit = () => form.post(route('addresses.store'));
</script>

<template>
    <AppLayout :title="a.create">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ a.create }}</h1>

            <AddressForm :form="form" :submit-label="a.create" @submit="submit" />
        </div>
    </AppLayout>
</template>
