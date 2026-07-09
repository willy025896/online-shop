<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AddressForm from './Partials/AddressForm.vue';

const props = defineProps({
    address: Object,
});

const page = usePage();
const a = computed(() => page.props.lang || {});

const form = useForm({
    label: props.address.label ?? '',
    recipient_name: props.address.recipient_name,
    phone: props.address.phone,
    address: props.address.address,
    is_default: props.address.is_default,
});

const submit = () => form.put(route('addresses.update', props.address.id));
</script>

<template>
    <AppLayout :title="a.edit">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ a.edit }}</h1>

            <AddressForm :form="form" :submit-label="a.update" @submit="submit" />
        </div>
    </AppLayout>
</template>
