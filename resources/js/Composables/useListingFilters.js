import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useInFlightLoading } from '@/Composables/useInFlightLoading';

/**
 * Shared "sort/rating select + apply-gated price range" request logic for the
 * product-listing pages that navigate per-click (Products/Index, Categories/Show).
 * Shop/Show uses a different reactive pattern (debounced watch-driven refs) and
 * is not a fit for this composable.
 *
 * getFilters() is called at request time (not memoized) so it always reads the
 * page's current `filters` prop, not a stale snapshot from setup().
 */
export function useListingFilters({ routeName, routeParams, getFilters }) {
    const { isLoading, start: startLoading, finish: finishLoading } = useInFlightLoading();

    const localMinPrice = ref(getFilters()?.min_price ?? '');
    const localMaxPrice = ref(getFilters()?.max_price ?? '');
    const hasPriceFilter = computed(() => localMinPrice.value || localMaxPrice.value);

    function updateFilters(partial) {
        router.get(route(routeName, routeParams ? routeParams() : undefined), { ...getFilters(), ...partial }, {
            preserveState: true,
            only: ['products', 'filters'],
            onStart: startLoading,
            onFinish: finishLoading,
        });
    }

    function applyPriceFilter() {
        updateFilters({
            min_price: localMinPrice.value || undefined,
            max_price: localMaxPrice.value || undefined,
        });
    }

    function clearPriceFilter() {
        localMinPrice.value = '';
        localMaxPrice.value = '';
        applyPriceFilter();
    }

    return {
        isLoading,
        startLoading,
        finishLoading,
        localMinPrice,
        localMaxPrice,
        hasPriceFilter,
        updateFilters,
        applyPriceFilter,
        clearPriceFilter,
    };
}
