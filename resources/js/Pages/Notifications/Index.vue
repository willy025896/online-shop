<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    notifications: Object,
    filter: String,
});

const page = usePage();
const lang = computed(() => page.props.lang || {});

const setFilter = (filter) => {
    router.get(route('notifications.index'), { filter }, { preserveScroll: true });
};

const markRead = (id) => {
    router.post(route('notifications.read', id), {}, { preserveScroll: true });
};

const markAllRead = () => {
    router.post(route('notifications.read_all'), {}, { preserveScroll: true });
};

const destroy = (id) => {
    router.delete(route('notifications.destroy', id), { preserveScroll: true });
};

const visit = (notification) => {
    if (!notification.read_at) markRead(notification.id);
    const url = notification.data?.url;
    if (url) router.visit(url);
};

const formatDate = (iso) => new Date(iso).toLocaleString();
</script>

<template>
    <AppLayout :title="lang.title">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ lang.title }}
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex gap-2 text-sm">
                            <button
                                @click="setFilter('all')"
                                :class="filter === 'all' ? 'text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            >{{ lang.all }}</button>
                            <span class="text-gray-300">|</span>
                            <button
                                @click="setFilter('unread')"
                                :class="filter === 'unread' ? 'text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            >{{ lang.unread }}</button>
                            <span class="text-gray-300">|</span>
                            <button
                                @click="setFilter('read')"
                                :class="filter === 'read' ? 'text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            >{{ lang.read }}</button>
                        </div>
                        <button
                            @click="markAllRead"
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                        >{{ lang.mark_all_read }}</button>
                    </div>

                    <div v-if="notifications.data.length === 0" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        {{ lang.empty }}
                    </div>

                    <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li
                            v-for="n in notifications.data"
                            :key="n.id"
                            :class="[
                                'px-6 py-4 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/40',
                                !n.read_at ? 'bg-indigo-50/40 dark:bg-indigo-900/20' : ''
                            ]"
                        >
                            <span v-if="!n.read_at" class="mt-2 h-2 w-2 rounded-full bg-indigo-500 shrink-0"></span>
                            <span v-else class="mt-2 h-2 w-2 shrink-0"></span>

                            <div class="flex-1 min-w-0 cursor-pointer" @click="visit(n)">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                    {{ n.data?.title }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
                                    {{ n.data?.body }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ formatDate(n.created_at) }}
                                </p>
                            </div>

                            <button
                                @click="destroy(n.id)"
                                class="text-xs text-gray-400 hover:text-red-600"
                                aria-label="Delete"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </li>
                    </ul>

                    <div v-if="notifications.last_page > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-center gap-1">
                        <template v-for="link in notifications.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                                ]"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
