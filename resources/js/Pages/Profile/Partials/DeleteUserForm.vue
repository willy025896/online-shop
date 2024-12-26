<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};

const page = usePage();
const actionLang = computed(() => page.props.lang.action);
const lang = computed(() => page.props.lang.profile.delete);
</script>

<template>
    <ActionSection>
        <template #title>
            {{ lang.title }}
        </template>

        <template #description>
            {{ lang.description }}
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
                {{ lang.content }}
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmUserDeletion">
                    {{ lang.title }}
                </DangerButton>
            </div>

            <!-- Delete Account Confirmation Modal -->
            <DialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title>
                    {{ lang.title }}
                </template>

                <template #content>
                    {{ lang.confirm }}

                    <div class="mt-4">
                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-3/4"
                            :placeholder="actionLang.password"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal">
                        {{ actionLang.cancel }}
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ lang.title }}
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
