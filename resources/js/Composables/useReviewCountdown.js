import { computed } from 'vue'

// Mirrors the server-side rule in app/Console/Commands/ReleaseReviews.php:
// the review window closes 14 days after order.completed_at.
const REVIEW_WINDOW_DAYS = 14
const DAY_MS = 24 * 60 * 60 * 1000

/**
 * Returns a computed integer: days left to review, 0 on the last day,
 * or null when the order is not eligible (not completed / already released / no completed_at).
 *
 * @param {() => object} orderRef A getter for the order (Inertia prop). Pass `() => props.order`.
 */
export function useReviewCountdown(orderRef) {
    return computed(() => {
        const order = orderRef()
        if (!order
            || order.status !== 'completed'
            || !order.completed_at
            || order.review_released_at) {
            return null
        }
        const completedAt = new Date(order.completed_at)
        const deadline = completedAt.getTime() + REVIEW_WINDOW_DAYS * DAY_MS
        const msLeft = deadline - Date.now()
        if (msLeft <= 0) return 0
        return Math.ceil(msLeft / DAY_MS)
    })
}
