import { computed, ref } from 'vue';

/**
 * Tracks how many independent in-flight requests are keeping a page in a
 * "loading" state (e.g. a filter change and a pagination click can overlap).
 * `isLoading` only clears once every request that called `start` has also
 * called `finish` — a single shared boolean would let whichever request
 * finishes first clear the flag while the other is still in flight.
 */
export function useInFlightLoading() {
    const inFlightCount = ref(0);

    const isLoading = computed(() => inFlightCount.value > 0);
    const start = () => { inFlightCount.value += 1; };
    const finish = () => { inFlightCount.value = Math.max(0, inFlightCount.value - 1); };

    return { isLoading, start, finish };
}
