<script setup>
import { computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import RowActions from '@/Components/RowActions.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Spinner from '@/Components/Spinner.vue';
import { useDeleteConfirmation } from '@/Composables/useDeleteConfirmation';
import { useAsyncActionGroup } from '@/Composables/useAsyncAction';

defineProps({
    addresses: Array,
});

const page = usePage();
const a = computed(() => page.props.lang || {});

const {
    pending: addressPendingDelete,
    label: addressPendingDeleteLabel,
    isDeleting,
    confirm: confirmDeleteAddress,
    cancel: cancelDeleteAddress,
    execute: deleteAddress,
} = useDeleteConfirmation('addresses.destroy', { labelField: 'recipient_name' });

const { isProcessing: isSettingDefault, run: runSetDefault } = useAsyncActionGroup();

const setDefault = (address) => {
    runSetDefault(address.id, (finish) => router.patch(route('addresses.default', address.id), {}, {
        preserveScroll: true,
        onFinish: finish,
    }));
};
</script>

<template>
    <AppLayout :title="a.title">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ a.title }}</h2>
                <Link :href="route('addresses.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    {{ a.add }}
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div v-if="!addresses.length" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-6 py-12 text-center text-gray-500">
                {{ a.no_addresses }} <Link :href="route('addresses.create')" class="text-indigo-600 hover:underline">{{ a.create_first }}</Link>.
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="address in addresses"
                    :key="address.id"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex flex-col"
                >
                    <div class="flex items-center gap-2 mb-2">
                        <span v-if="address.label" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            {{ address.label }}
                        </span>
                        <span v-if="address.is_default" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                            {{ a.default_badge }}
                        </span>
                    </div>

                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ address.recipient_name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ address.phone }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex-1">{{ address.address }}</p>

                    <div class="mt-4 flex items-center justify-between text-sm">
                        <RowActions :loading="isDeleting(address.id)">
                            <Link :href="route('addresses.edit', address.id)" class="text-indigo-600 hover:text-indigo-900">{{ a.action_edit }}</Link>
                            <button @click="confirmDeleteAddress(address)" class="text-red-600 hover:text-red-900">{{ a.action_delete }}</button>
                        </RowActions>

                        <button
                            v-if="!address.is_default"
                            @click="setDefault(address)"
                            :disabled="isSettingDefault(address.id)"
                            class="flex items-center gap-1 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 disabled:opacity-50"
                        >
                            <Spinner v-if="isSettingDefault(address.id)" class="h-3.5 w-3.5" />
                            {{ a.action_set_default }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal :show="addressPendingDelete !== null" @close="cancelDeleteAddress">
            <template #title>{{ a.action_delete }}</template>
            <template #content>
                {{ (a.delete_confirm || 'Delete ":name"?').replace(':name', addressPendingDeleteLabel) }}
            </template>
            <template #footer>
                <SecondaryButton @click="cancelDeleteAddress">{{ a.cancel }}</SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': addressPendingDelete && isDeleting(addressPendingDelete.id) }"
                    :disabled="addressPendingDelete && isDeleting(addressPendingDelete.id)"
                    @click="deleteAddress"
                >
                    {{ a.confirm }}
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
