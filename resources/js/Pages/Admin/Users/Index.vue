<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Spinner from '@/Components/Spinner.vue';
import TableSkeletonRows from '@/Components/TableSkeletonRows.vue';
import { useAsyncActionGroup } from '@/Composables/useAsyncAction';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    users: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
const isLoading = ref(false);
const skeletonRows = computed(() => props.users.data.length || props.users.per_page || 5);
const toast = useToast();

const { isProcessing: isUpdating, run } = useAsyncActionGroup();

const updateRole = (user, role) => {
    run(user.id, (finish) => router.patch(route('admin.users.role', user.id), { role }, {
        onError: (errors) => toast.error(errors.role),
        onFinish: finish,
    }));
};
</script>

<template>
    <AdminLayout :title="lang.users?.title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.users?.title }}</h2>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.users?.name }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.users?.email }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.users?.role }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.users?.joined }}</th>
                    </tr>
                </thead>
                <tbody v-if="isLoading" role="status" aria-busy="true" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <TableSkeletonRows :columns="4" :rows="skeletonRows" />
                </tbody>
                <tbody v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-if="users.data.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ lang.users?.no_users }}</p>
                        </td>
                    </tr>
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <select
                                    :value="user.role"
                                    @change="updateRole(user, $event.target.value)"
                                    :disabled="isUpdating(user.id)"
                                    class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 disabled:opacity-50"
                                >
                                    <option value="customer">{{ lang.users?.customer }}</option>
                                    <option value="seller">{{ lang.users?.seller }}</option>
                                    <option value="admin">{{ lang.users?.admin }}</option>
                                </select>
                                <Spinner v-if="isUpdating(user.id)" class="h-4 w-4 text-gray-400" />
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <Pagination :links="users.links" @start="isLoading = true" @finish="isLoading = false" />
        </div>
    </AdminLayout>
</template>
