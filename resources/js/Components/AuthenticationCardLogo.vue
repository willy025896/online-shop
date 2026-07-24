<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'">
        <svg
            class="w-16 h-16"
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect x="2" y="2" width="44" height="44" rx="14" fill="#1E3A5F" />
            <text x="24" y="32" text-anchor="middle" font-family="Figtree, ui-sans-serif, system-ui, sans-serif" font-weight="800" font-size="22" fill="#FFFFFF">M</text>
            <circle cx="39" cy="9" r="5" fill="#E8985C" />
        </svg>
    </Link>
</template>
