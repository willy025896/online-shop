<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { combinationKey } from '@/Utils/variantCombination';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    product: Object,
    lang: Object,
});

const toast = useToast();

let tempCounter = 0;
const nextTempKey = () => `new-${tempCounter++}`;

const buildOptions = (product) => (product.options || []).map((option) => ({
    id: option.id,
    name: option.name,
    sort_order: option.sort_order,
    values: (option.values || []).map((value) => ({
        id: value.id,
        key: String(value.id),
        value: value.value,
        sort_order: value.sort_order,
    })),
}));

const buildVariants = (product) => (product.variants || []).map((variant) => ({
    id: variant.id,
    sku: variant.sku,
    price: variant.price,
    compare_price: variant.compare_price || '',
    stock: variant.stock,
    option_value_keys: (variant.option_values || []).map((ov) => String(ov.id)),
}));

const options = ref(buildOptions(props.product));
const variants = ref(buildVariants(props.product));

const addOption = () => {
    options.value.push({ id: null, name: '', sort_order: options.value.length, values: [] });
};

const removeOption = (index) => {
    const removedKeys = new Set(options.value[index].values.map((v) => v.key));
    options.value.splice(index, 1);
    // Any variant referencing a value from the removed option is no longer valid.
    variants.value = variants.value.filter(
        (variant) => !variant.option_value_keys.some((key) => removedKeys.has(key)),
    );
};

const addValue = (option) => {
    option.values.push({ id: null, key: nextTempKey(), value: '', sort_order: option.values.length });
};

const removeValue = (option, index) => {
    const removedKey = option.values[index].key;
    option.values.splice(index, 1);
    variants.value = variants.value.filter(
        (variant) => !variant.option_value_keys.includes(removedKey),
    );
};

// key -> "Option: Value", built once per options change instead of a nested
// scan of every option/value on every combinationLabel() call.
const labelByKey = computed(() => {
    const map = new Map();
    for (const option of options.value) {
        for (const value of option.values) {
            map.set(value.key, `${option.name}: ${value.value}`);
        }
    }
    return map;
});

const combinationLabel = (keys) => keys.map((key) => labelByKey.value.get(key)).filter(Boolean).join(' / ');

const generateCombinations = () => {
    const valueLists = options.value.filter((o) => o.values.length > 0).map((o) => o.values.map((v) => v.key));
    if (valueLists.length === 0) {
        toast.error(props.lang?.select_options_first || 'Add options and values first');
        return;
    }

    let combos = [[]];
    for (const values of valueLists) {
        combos = combos.flatMap((combo) => values.map((v) => [...combo, v]));
    }

    const existingCombos = new Set(variants.value.map((v) => combinationKey(v.option_value_keys)));

    combos.forEach((keys) => {
        if (!existingCombos.has(combinationKey(keys))) {
            variants.value.push({
                id: null,
                sku: '',
                price: props.product.price,
                compare_price: '',
                stock: 0,
                option_value_keys: keys,
            });
        }
    });
};

const removeVariant = (index) => {
    variants.value.splice(index, 1);
};

const form = useForm({ options: [], variants: [] });

const submit = () => {
    form.options = options.value;
    form.variants = variants.value;

    form.patch(route('seller.products.variants.update', props.product.id), {
        preserveScroll: true,
        onSuccess: (page) => {
            // Re-sync local state from the server-assigned ids — otherwise a
            // second save without a full page reload would resubmit every
            // row as brand-new (id: null), tripping SKU uniqueness.
            const product = page.props.product;
            options.value = buildOptions(product);
            variants.value = buildVariants(product);
            toast.success(props.lang?.save || 'Variants updated.');
        },
        onError: (errors) => toast.error(errors.variants || errors.options || 'Failed to save variants.'),
    });
};
</script>

<template>
    <div class="space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ lang?.hint }}</p>

        <div class="space-y-4">
            <div
                v-for="(option, optionIndex) in options"
                :key="option.id || optionIndex"
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
            >
                <div class="flex items-center gap-2 mb-3">
                    <TextInput
                        v-model="option.name"
                        type="text"
                        :placeholder="lang?.option_name"
                        class="flex-1"
                    />
                    <button
                        type="button"
                        @click="removeOption(optionIndex)"
                        class="text-sm text-red-600 hover:text-red-800"
                    >
                        {{ lang?.remove_option }}
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(value, valueIndex) in option.values"
                        :key="value.key"
                        class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded px-2 py-1"
                    >
                        <input
                            v-model="value.value"
                            type="text"
                            :placeholder="lang?.value_placeholder"
                            class="bg-transparent border-0 focus:ring-0 text-sm w-20 dark:text-gray-200"
                        />
                        <button type="button" @click="removeValue(option, valueIndex)" class="text-gray-400 hover:text-red-600">
                            &times;
                        </button>
                    </div>
                    <button
                        type="button"
                        @click="addValue(option)"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        + {{ lang?.add_value }}
                    </button>
                </div>
            </div>

            <button type="button" @click="addOption" class="text-sm text-indigo-600 hover:text-indigo-800">
                + {{ lang?.add_option }}
            </button>
        </div>

        <div>
            <button
                type="button"
                @click="generateCombinations"
                class="text-sm px-3 py-1.5 rounded border border-indigo-300 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
            >
                {{ lang?.generate }}
            </button>
        </div>

        <div v-if="variants.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            {{ lang?.no_variants }}
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-3">—</th>
                        <th class="py-2 px-3">{{ lang?.sku }}</th>
                        <th class="py-2 px-3">{{ lang?.price }}</th>
                        <th class="py-2 px-3">{{ lang?.compare_price }}</th>
                        <th class="py-2 px-3">{{ lang?.stock }}</th>
                        <th class="py-2 px-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(variant, index) in variants" :key="variant.id || index" class="border-t border-gray-100 dark:border-gray-700">
                        <td class="py-2 pr-3 text-gray-700 dark:text-gray-300">{{ combinationLabel(variant.option_value_keys) }}</td>
                        <td class="py-2 px-3"><TextInput v-model="variant.sku" type="text" class="w-32" /></td>
                        <td class="py-2 px-3"><TextInput v-model="variant.price" type="number" step="0.01" min="0" class="w-24" /></td>
                        <td class="py-2 px-3"><TextInput v-model="variant.compare_price" type="number" step="0.01" min="0" class="w-24" /></td>
                        <td class="py-2 px-3"><TextInput v-model="variant.stock" type="number" min="0" class="w-20" /></td>
                        <td class="py-2 px-3">
                            <button type="button" @click="removeVariant(index)" class="text-red-600 hover:text-red-800 text-xs">
                                {{ lang?.remove_variant }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <InputError :message="form.errors.variants || form.errors.options" class="mt-2" />

        <div class="flex justify-end">
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">
                {{ lang?.save }}
            </PrimaryButton>
        </div>
    </div>
</template>
