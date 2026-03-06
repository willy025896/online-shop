<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    categories: Array,
    allCategories: Array,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const showForm = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    parent_id: '',
    sort_order: 0,
    is_active: true,
});

const startCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    showForm.value = true;
};

const startEdit = (category) => {
    editing.value = category;
    form.name = category.name;
    form.parent_id = category.parent_id || '';
    form.sort_order = category.sort_order;
    form.is_active = category.is_active;
    showForm.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('admin.categories.update', editing.value.id), {
            onSuccess: () => { showForm.value = false; },
        });
    } else {
        form.post(route('admin.categories.store'), {
            onSuccess: () => { showForm.value = false; form.reset(); },
        });
    }
};

const deleteCategory = (category) => {
    if (confirm(lang.value.categories?.delete_confirm?.replace(':name', category.name))) {
        router.delete(route('admin.categories.destroy', category.id));
    }
};
</script>

<template>
    <AdminLayout :title="lang.categories?.title">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.categories?.title }}</h2>
                <button @click="startCreate" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    {{ lang.categories?.add }}
                </button>
            </div>
        </template>

        <!-- Create/Edit Form -->
        <div v-if="showForm" class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                {{ editing ? lang.categories?.edit : lang.categories?.new }}
            </h3>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="name" :value="lang.categories?.name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="parent_id" :value="lang.categories?.parent" />
                        <select
                            id="parent_id"
                            v-model="form.parent_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{{ lang.categories?.none }}</option>
                            <option
                                v-for="cat in allCategories"
                                :key="cat.id"
                                :value="cat.id"
                                :disabled="editing && cat.id === editing.id"
                            >{{ cat.name }}</option>
                        </select>
                        <InputError :message="form.errors.parent_id" class="mt-2" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="sort_order" :value="lang.categories?.order" />
                        <TextInput id="sort_order" v-model="form.sort_order" type="number" min="0" class="mt-1 block w-full" />
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ lang.categories?.active }}</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-2">
                    <PrimaryButton :disabled="form.processing">{{ editing ? lang.categories?.update : lang.categories?.create }}</PrimaryButton>
                    <button type="button" @click="showForm = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">{{ lang.categories?.cancel }}</button>
                </div>
            </form>
        </div>

        <!-- Categories List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table v-if="categories.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.categories?.name }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.categories?.subcategories }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.categories?.order }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.categories?.active }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ lang.categories?.actions }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template v-for="category in categories" :key="category.id">
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ category.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ category.children?.length || 0 }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ category.sort_order }}</td>
                            <td class="px-6 py-4">
                                <span :class="[category.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800', 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium']">
                                    {{ category.is_active ? lang.categories?.active : lang.categories?.inactive }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <button @click="startEdit(category)" class="text-indigo-600 hover:text-indigo-900">{{ lang.categories?.action_edit }}</button>
                                <button @click="deleteCategory(category)" class="text-red-600 hover:text-red-900">{{ lang.categories?.action_delete }}</button>
                            </td>
                        </tr>
                        <!-- Children -->
                        <tr v-for="child in category.children" :key="child.id" class="bg-gray-50 dark:bg-gray-750">
                            <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300 pl-12">-- {{ child.name }}</td>
                            <td class="px-6 py-3 text-sm text-gray-500">-</td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ child.sort_order }}</td>
                            <td class="px-6 py-3">
                                <span :class="[child.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800', 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium']">
                                    {{ child.is_active ? lang.categories?.active : lang.categories?.inactive }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right text-sm space-x-2">
                                <button @click="startEdit(child)" class="text-indigo-600 hover:text-indigo-900">{{ lang.categories?.action_edit }}</button>
                                <button @click="deleteCategory(child)" class="text-red-600 hover:text-red-900">{{ lang.categories?.action_delete }}</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-gray-500">
                {{ lang.categories?.no_categories }}
            </div>
        </div>
    </AdminLayout>
</template>
