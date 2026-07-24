// Estimates how many skeleton rows to render while a paginated list is loading:
// prefer the current page's actual row count, falling back to per_page when the
// current page is empty (e.g. after deleting the last item on the last page).
export function skeletonRowCount(pagination, fallback = 5) {
    return pagination?.data?.length || pagination?.per_page || fallback;
}
