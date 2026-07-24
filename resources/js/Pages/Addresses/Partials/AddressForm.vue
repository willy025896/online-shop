<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'Save' },
});

const page = usePage();
const a = computed(() => page.props.lang || {});

const quickLabels = computed(() => [a.value.label_home, a.value.label_office, a.value.label_other].filter(Boolean));

const emit = defineEmits(['submit']);
</script>

<template>
    <form @submit.prevent="emit('submit')" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-6 max-w-2xl">
        <div>
            <InputLabel for="label" :value="a.label" />
            <div v-if="quickLabels.length" class="flex gap-2 mt-1 mb-2">
                <button
                    v-for="quick in quickLabels"
                    :key="quick"
                    type="button"
                    @click="form.label = quick"
                    :class="[
                        'px-3 py-1 rounded-full text-xs font-medium border',
                        form.label === quick
                            ? 'bg-brand-500 text-white border-brand-500'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-transparent hover:bg-gray-200 dark:hover:bg-gray-600',
                    ]"
                >
                    {{ quick }}
                </button>
            </div>
            <TextInput id="label" v-model="form.label" type="text" class="block w-full" />
            <InputError :message="form.errors.label" class="mt-2" />
        </div>

        <div>
            <InputLabel for="recipient_name" :value="a.recipient_name" />
            <TextInput id="recipient_name" v-model="form.recipient_name" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.recipient_name" class="mt-2" />
        </div>

        <div>
            <InputLabel for="phone" :value="a.phone" />
            <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.phone" class="mt-2" />
        </div>

        <div>
            <InputLabel for="address" :value="a.address" />
            <textarea
                id="address"
                v-model="form.address"
                rows="3"
                required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-brand-400 focus:ring-accent-400"
            ></textarea>
            <InputError :message="form.errors.address" class="mt-2" />
        </div>

        <div class="flex items-center">
            <Checkbox id="is_default" v-model:checked="form.is_default" />
            <label for="is_default" class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ a.set_default }}</label>
        </div>

        <div class="flex justify-end">
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ submitLabel }}
            </PrimaryButton>
        </div>
    </form>
</template>
