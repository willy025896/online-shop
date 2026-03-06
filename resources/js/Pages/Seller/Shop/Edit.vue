<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    shop: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const form = useForm({
    name: props.shop.name,
    description: props.shop.description || '',
    logo: null,
});

const submit = () => {
    form.post(route('seller.shop.update'), {
        method: 'put',
        forceFormData: true,
    });
};
</script>

<template>
    <SellerLayout :title="lang.shop?.title">
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ lang.shop?.title }}</h2>
        </template>

        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6">
                <!-- Current Logo -->
                <div v-if="shop.logo_path">
                    <InputLabel :value="lang.shop?.current_logo" />
                    <img :src="`/storage/${shop.logo_path}`" class="mt-2 h-20 w-20 rounded-lg object-cover" />
                </div>

                <div>
                    <InputLabel for="logo" :value="lang.shop?.logo" />
                    <input
                        id="logo"
                        type="file"
                        accept="image/*"
                        @change="form.logo = $event.target.files[0]"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                    />
                    <InputError :message="form.errors.logo" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="name" :value="lang.shop?.name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="description" :value="lang.shop?.description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        {{ lang.shop?.save }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </SellerLayout>
</template>
