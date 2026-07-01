<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'Save' },
});

const page = usePage();
const c = computed(() => page.props.lang?.coupons || {});

const isPercentage = computed(() => props.form.type === 'percentage');

const selectClass =
    'mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

const emit = defineEmits(['submit']);
</script>

<template>
    <form @submit.prevent="emit('submit')" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6 max-w-2xl">
        <div>
            <InputLabel for="code" :value="c.code" />
            <TextInput id="code" v-model="form.code" type="text" class="mt-1 block w-full uppercase" required />
            <InputError :message="form.errors.code" class="mt-2" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel for="type" :value="c.type" />
                <select id="type" v-model="form.type" :class="selectClass">
                    <option value="percentage">{{ c.type_percentage }}</option>
                    <option value="fixed">{{ c.type_fixed }}</option>
                </select>
                <InputError :message="form.errors.type" class="mt-2" />
            </div>
            <div>
                <InputLabel for="value" :value="c.value" />
                <TextInput id="value" v-model="form.value" type="number" step="0.01" min="0.01"
                    :max="isPercentage ? 100 : undefined" class="mt-1 block w-full" required />
                <p class="mt-1 text-xs text-gray-500">{{ isPercentage ? c.value_hint_percentage : c.value_hint_fixed }}</p>
                <InputError :message="form.errors.value" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel for="min_spend" :value="c.min_spend" />
                <TextInput id="min_spend" v-model="form.min_spend" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                <InputError :message="form.errors.min_spend" class="mt-2" />
            </div>
            <div v-if="isPercentage">
                <InputLabel for="max_discount" :value="c.max_discount" />
                <TextInput id="max_discount" v-model="form.max_discount" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                <InputError :message="form.errors.max_discount" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel for="usage_limit" :value="c.usage_limit" />
                <TextInput id="usage_limit" v-model="form.usage_limit" type="number" min="1" :placeholder="c.unlimited" class="mt-1 block w-full" />
                <InputError :message="form.errors.usage_limit" class="mt-2" />
            </div>
            <div>
                <InputLabel for="per_user_limit" :value="c.per_user_limit" />
                <TextInput id="per_user_limit" v-model="form.per_user_limit" type="number" min="1" :placeholder="c.unlimited" class="mt-1 block w-full" />
                <InputError :message="form.errors.per_user_limit" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel for="starts_at" :value="c.starts_at" />
                <TextInput id="starts_at" v-model="form.starts_at" type="date" class="mt-1 block w-full" />
                <InputError :message="form.errors.starts_at" class="mt-2" />
            </div>
            <div>
                <InputLabel for="expires_at" :value="c.expires_at" />
                <TextInput id="expires_at" v-model="form.expires_at" type="date" class="mt-1 block w-full" />
                <InputError :message="form.errors.expires_at" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center">
            <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
            <label for="is_active" class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ c.is_active }}</label>
        </div>

        <div class="flex justify-end">
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ submitLabel }}
            </PrimaryButton>
        </div>
    </form>
</template>
