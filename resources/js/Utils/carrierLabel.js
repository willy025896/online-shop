// Looks up a carrier's display label from the page's i18n `carriers` map,
// falling back to the raw carrier code when the map has no entry for it.
export function carrierLabel(carriers, carrier) {
    return (carriers && carriers[carrier]) || carrier;
}
