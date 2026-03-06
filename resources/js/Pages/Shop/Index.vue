<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    shops: Object,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});
</script>

<template>
    <AppLayout :title="lang.title">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ lang.all_shops }}</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="shop in shops.data"
                    :key="shop.id"
                    :href="route('shops.show', shop.slug)"
                    class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition p-6"
                >
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
                            <img v-if="shop.logo_path" :src="`/storage/${shop.logo_path}`" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-400">
                                {{ shop.name[0] }}
                            </div>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ shop.name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ shop.products_count }} {{ lang.products }}</p>
                        </div>
                    </div>
                    <p v-if="shop.description" class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ shop.description }}</p>
                </Link>
            </div>

            <div class="mt-8">
                <Pagination :links="shops.links" />
            </div>
        </div>
    </AppLayout>
</template>
