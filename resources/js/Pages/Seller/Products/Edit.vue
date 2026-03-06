<script setup>
import { useForm } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ImageUploader from '@/Components/ImageUploader.vue';

const props = defineProps({
    product: Object,
    categories: Array,
});

const form = useForm({
    name: props.product.name,
    description: props.product.description || '',
    category_id: props.product.category_id || '',
    price: props.product.price,
    compare_price: props.product.compare_price || '',
    stock: props.product.stock,
    status: props.product.status,
    is_featured: props.product.is_featured,
});

const submit = () => {
    form.put(route('seller.products.update', props.product.id));
};
</script>

<template>
    <SellerLayout title="Edit Product">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Edit Product</h2>
        </template>

        <div class="max-w-3xl space-y-6">
            <!-- Images -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Product Images</h3>
                <ImageUploader :product="product" :images="product.images || []" />
            </div>

            <!-- Product Form -->
            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6">
                <div>
                    <InputLabel for="name" value="Product Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="category_id" value="Category" />
                    <select
                        id="category_id"
                        v-model="form.category_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">No category</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                    <InputError :message="form.errors.category_id" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="price" value="Price ($)" />
                        <TextInput id="price" v-model="form.price" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.price" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="compare_price" value="Compare Price ($)" />
                        <TextInput id="compare_price" v-model="form.compare_price" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                        <InputError :message="form.errors.compare_price" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="stock" value="Stock" />
                        <TextInput id="stock" v-model="form.stock" type="number" min="0" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.stock" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="status" value="Status" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center">
                    <input id="is_featured" v-model="form.is_featured" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                    <label for="is_featured" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Featured product</label>
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Update Product
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </SellerLayout>
</template>