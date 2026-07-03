<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    result: { type: Object, default: null },
});

const page = usePage();
const t = computed(() => page.props.lang?.products || {});

const form = useForm({
    file: null,
});

const submit = () => {
    form.post(route('seller.products.import'), {
        forceFormData: true,
        onSuccess: () => { form.reset(); },
    });
};
</script>

<template>
    <SellerLayout :title="t.import_title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ t.import_title }}</h2>
        </template>

        <div class="max-w-2xl space-y-6">
            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6">
                <div>
                    <InputLabel for="file" :value="t.import_file" />
                    <input
                        id="file"
                        type="file"
                        accept=".csv,text/csv"
                        @change="form.file = $event.target.files[0]"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                    />
                    <InputError :message="form.errors.file" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        {{ t.import_submit }}
                    </PrimaryButton>
                </div>
            </form>

            <div v-if="result" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t.import_result }}</h3>
                <div class="flex gap-6 text-sm">
                    <span class="text-green-700 dark:text-green-400">{{ t.created }}: {{ result.created }}</span>
                    <span class="text-indigo-700 dark:text-indigo-400">{{ t.updated }}: {{ result.updated }}</span>
                    <span class="text-red-700 dark:text-red-400">{{ t.failed }}: {{ result.failed.length }}</span>
                </div>

                <table v-if="result.failed.length" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.row }}</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ t.reason }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="failure in result.failed" :key="failure.row">
                            <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ failure.row }}</td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ failure.reason }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </SellerLayout>
</template>
