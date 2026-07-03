import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAsyncActionGroup } from '@/Composables/useAsyncAction';
import { useToast } from '@/Composables/useToast';

/**
 * Drives a row-delete confirmation modal: tracks the pending item, a display
 * label snapshot (kept during the modal's close transition so it doesn't
 * flash "undefined"), and the delete request itself.
 */
export function useDeleteConfirmation(routeName, { labelField = 'name', onError } = {}) {
    const { isProcessing: isDeleting, run } = useAsyncActionGroup();
    const toast = useToast();

    const pending = ref(null);
    const label = ref('');

    const confirm = (item) => {
        pending.value = item;
        label.value = item[labelField];
    };

    const cancel = () => {
        pending.value = null;
    };

    const execute = () => {
        const item = pending.value;
        run(item.id, (finish) => router.delete(route(routeName, item.id), {
            onSuccess: () => { pending.value = null; },
            onError: onError ? (errors) => onError(errors, toast) : undefined,
            onFinish: finish,
        }));
    };

    return { pending, label, isDeleting, confirm, cancel, execute };
}
