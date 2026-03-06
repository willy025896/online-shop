<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    name: '',
    slug: '',
    description: '',
});

const submit = () => {
    form.post(route('seller.register.store'));
};
</script>

<template>
    <AppLayout title="Register as Seller">
        <div class="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Register as a Seller</h1>
                <p class="text-gray-600 dark:text-gray-400 mb-8">Set up your shop and start selling.</p>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="name" value="Shop Name" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Shop URL" />
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 text-sm text-gray-500 dark:text-gray-400">
                                /shop/
                            </span>
                            <TextInput
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="block w-full rounded-l-none"
                                required
                            />
                        </div>
                        <InputError :message="form.errors.slug" class="mt-2" />
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

                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Register Shop
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </AppLayout>
</template>