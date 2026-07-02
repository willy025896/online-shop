import { reactive, ref } from 'vue';

/**
 * Tracks a single in-flight async action (e.g. a button's own request).
 * `run` sets `processing` true immediately and expects the wrapped action
 * to call the `finish` callback it receives once the action settles
 * (Inertia's `onFinish`, or `.finally(finish)` for a promise-based call).
 */
export function useAsyncAction() {
    const processing = ref(false);

    const run = (action) => {
        if (processing.value) return;
        processing.value = true;
        action(() => { processing.value = false; });
    };

    return { processing, run };
}

/**
 * Same as useAsyncAction, but tracks many concurrent actions keyed by id
 * (e.g. a delete button per row in a list).
 */
export function useAsyncActionGroup() {
    const processingIds = reactive(new Set());

    const isProcessing = (id) => processingIds.has(id);

    const run = (id, action) => {
        if (processingIds.has(id)) return;
        processingIds.add(id);
        action(() => { processingIds.delete(id); });
    };

    return { processingIds, isProcessing, run };
}
