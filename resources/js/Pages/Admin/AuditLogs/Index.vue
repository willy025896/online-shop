<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import TableSkeletonRows from '@/Components/TableSkeletonRows.vue';

const props = defineProps({
    logs: Object,
    filters: { type: Object, default: () => ({}) },
    actionOptions: { type: Array, default: () => [] },
    adminOptions: { type: Array, default: () => [] },
});

const page = usePage();
const t = computed(() => page.props.lang?.audit_logs || {});
const isLoading = ref(false);
const skeletonRows = computed(() => props.logs.data.length || props.logs.per_page || 5);

const actionFilter = ref(props.filters.action || '');
const adminFilter = ref(props.filters.admin_id || '');

const applyFilters = () => {
    router.get(
        route('admin.audit-logs.index'),
        {
            action: actionFilter.value || undefined,
            admin_id: adminFilter.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => { isLoading.value = true; },
            onFinish: () => { isLoading.value = false; },
        },
    );
};

const actionLabel = (action) => t.value.actions_map?.[action] || action;

const formatDateTime = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString();
};

const subjectLabel = (log) => `${log.subject_type.split('\\').pop()} #${log.subject_id}`;

const changesLabel = (log) => {
    const changes = log.changes || {};
    if (changes.from !== undefined && changes.to !== undefined) {
        return `${changes.from} → ${changes.to}`;
    }
    if (changes.name) return changes.name;
    if (changes.code) return changes.code;
    if (Object.keys(changes).length) {
        return Object.entries(changes).map(([k, v]) => `${k}: ${v}`).join(', ');
    }
    return '-';
};
</script>

<template>
    <AdminLayout :title="t.title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ t.title }}</h2>
        </template>

        <div class="mb-4 flex flex-wrap items-center gap-3">
            <select
                v-model="actionFilter"
                @change="applyFilters"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">{{ t.all_actions }}</option>
                <option v-for="action in actionOptions" :key="action" :value="action">{{ actionLabel(action) }}</option>
            </select>

            <select
                v-model="adminFilter"
                @change="applyFilters"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">{{ t.all_admins }}</option>
                <option v-for="admin in adminOptions" :key="admin.id" :value="admin.id">{{ admin.name }}</option>
            </select>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-x-auto">
            <table v-if="isLoading || logs.data.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.when }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.admin }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.action }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.subject }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.changes }}</th>
                    </tr>
                </thead>
                <tbody v-if="isLoading" role="status" aria-busy="true" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <TableSkeletonRows :columns="5" :rows="skeletonRows" />
                </tbody>
                <tbody v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="log in logs.data" :key="log.id">
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(log.created_at) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ log.admin?.name || t.system }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ actionLabel(log.action) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ subjectLabel(log) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ changesLabel(log) }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-gray-500">
                {{ t.no_logs }}
            </div>
        </div>

        <div class="mt-6">
            <Pagination :links="logs.links" @start="isLoading = true" @finish="isLoading = false" />
        </div>
    </AdminLayout>
</template>
