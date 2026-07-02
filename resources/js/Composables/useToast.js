import { reactive } from 'vue';

const DEFAULT_DURATION = 4000;

const toasts = reactive([]);
const timers = new Map(); // kept outside `toasts` so setTimeout ids aren't made reactive
let nextId = 1;

function dismiss(id) {
    const index = toasts.findIndex((t) => t.id === id);
    if (index === -1) return;

    clearTimeout(timers.get(id));
    timers.delete(id);
    toasts.splice(index, 1);
}

function push(type, message, duration = DEFAULT_DURATION) {
    if (!message) return;

    const id = nextId++;
    timers.set(id, setTimeout(() => dismiss(id), duration));
    toasts.push({ id, type, message });

    return id;
}

export function useToast() {
    return {
        toasts,
        push,
        success: (message, duration) => push('success', message, duration),
        error: (message, duration) => push('error', message, duration),
        dismiss,
    };
}
