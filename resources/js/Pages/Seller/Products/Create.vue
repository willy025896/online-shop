<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    categories: Array,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const form = useForm({
    name: '',
    description: '',
    category_id: '',
    price: '',
    compare_price: '',
    stock: 0,
    status: 'draft',
    is_featured: false,
});

const submit = () => {
    form.post(route('seller.products.store'));
};
</script>

<template>
    <SellerLayout :title="lang.products?.create">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.products?.create }}</h2>
        </template>

        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6">
                <div>
                    <InputLabel for="name" :value="lang.products?.name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="description" :value="lang.products?.description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="category_id" :value="lang.products?.category" />
                    <select
                        id="category_id"
                        v-model="form.category_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">{{ lang.products?.no_category }}</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                    <InputError :message="form.errors.category_id" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="price" :value="lang.products?.price" />
                        <TextInput id="price" v-model="form.price" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.price" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="compare_price" :value="lang.products?.compare_price" />
                        <TextInput id="compare_price" v-model="form.compare_price" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                        <InputError :message="form.errors.compare_price" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="stock" :value="lang.products?.stock" />
                        <TextInput id="stock" v-model="form.stock" type="number" min="0" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.stock" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="status" :value="lang.products?.status" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="draft">{{ lang.products?.draft }}</option>
                            <option value="active">{{ lang.products?.active }}</option>
                            <option value="inactive">{{ lang.products?.inactive }}</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center">
                    <input id="is_featured" v-model="form.is_featured" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                    <label for="is_featured" class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ lang.products?.featured }}</label>
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        {{ lang.products?.create }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </SellerLayout>
</template>
