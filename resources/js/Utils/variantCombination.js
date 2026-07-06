// Canonical key for a set of option-value identifiers (ids or client-side temp
// keys), used to detect duplicate/matching option combinations. Order of the
// input never matters — the values are sorted before joining.
export function combinationKey(values) {
    return [...values].map(String).sort().join(',');
}
