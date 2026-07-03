<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';

const page = usePage();

const userId = computed(() => page.props.auth?.user?.id);
const notificationLang = computed(() => page.props.notificationBellLang || {});

// Local mirrors so that broadcast pushes can update the badge without a full Inertia reload.
const unreadCount = ref(page.props.unreadNotificationCount || 0);
const items = ref([...(page.props.recentNotifications || [])]);

const fallbackText = (key, fallback) => notificationLang.value[key] ?? fallback;

const formatTime = (iso) => {
    if (!iso) return '';
    const date = new Date(iso);
    const diff = Date.now() - date.getTime();
    if (diff < 60_000) return fallbackText('just_now', 'just now');
    const minutes = Math.floor(diff / 60_000);
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d`;
    return date.toLocaleDateString();
};

const refreshFromProps = () => {
    unreadCount.value = page.props.unreadNotificationCount || 0;
    items.value = [...(page.props.recentNotifications || [])];
};

let echoChannelName = null;

onMounted(() => {
    refreshFromProps();

    if (!userId.value || !window.Echo) return;

    echoChannelName = `App.Models.User.${userId.value}`;
    window.Echo.private(echoChannelName).notification((payload) => {
        unreadCount.value += 1;
        items.value = [
            {
                id: payload.id ?? crypto.randomUUID(),
                read_at: null,
                created_at: new Date().toISOString(),
                data: payload,
            },
            ...items.value,
        ].slice(0, 10);
    });
});

onBeforeUnmount(() => {
    if (echoChannelName && window.Echo) {
        window.Echo.leave(echoChannelName);
    }
});

const handleClick = (notification) => {
    if (!notification.read_at) {
        const previousReadAt = notification.read_at;
        const previousUnread = unreadCount.value;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        notification.read_at = new Date().toISOString();
        router.post(route('notifications.read', notification.id), {}, {
            preserveScroll: true,
            preserveState: true,
            only: ['unreadNotificationCount', 'recentNotifications'],
            onError: () => {
                notification.read_at = previousReadAt;
                unreadCount.value = previousUnread;
            },
        });
    }
    const url = notification.data?.url;
    if (url) {
        router.visit(url);
    }
};

const markAllRead = () => {
    const snapshot = items.value.map((n) => ({ id: n.id, read_at: n.read_at }));
    const previousUnread = unreadCount.value;
    unreadCount.value = 0;
    items.value.forEach((n) => { n.read_at = n.read_at || new Date().toISOString(); });
    router.post(route('notifications.read_all'), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['unreadNotificationCount', 'recentNotifications'],
        onError: () => {
            unreadCount.value = previousUnread;
            const lookup = new Map(snapshot.map((s) => [s.id, s.read_at]));
            items.value.forEach((n) => {
                if (lookup.has(n.id)) n.read_at = lookup.get(n.id);
            });
        },
    });
};
</script>

<template>
    <div v-if="userId" class="relative">
        <Dropdown align="right" width="80">
            <template #trigger>
                <button
                    :aria-label="fallbackText('title', 'Notifications')"
                    class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                        {{ unreadCount > 99 ? '99+' : unreadCount }}
                    </span>
                </button>
            </template>

            <template #content>
                <div class="w-80 max-h-[28rem] overflow-y-auto bg-white dark:bg-gray-800">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ fallbackText('title', 'Notifications') }}
                        </span>
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllRead"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            {{ fallbackText('mark_all_read', 'Mark all as read') }}
                        </button>
                    </div>

                    <div v-if="items.length === 0" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ fallbackText('empty', 'No notifications yet.') }}
                    </div>

                    <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li
                            v-for="n in items"
                            :key="n.id"
                            @click="handleClick(n)"
                            :class="[
                                'px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700',
                                !n.read_at ? 'bg-indigo-50/40 dark:bg-indigo-900/20' : ''
                            ]"
                        >
                            <div class="flex items-start gap-2">
                                <span v-if="!n.read_at" class="mt-1.5 h-2 w-2 rounded-full bg-indigo-500 shrink-0"></span>
                                <span v-else class="mt-1.5 h-2 w-2 shrink-0"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">
                                        {{ n.data?.title }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                        {{ n.data?.body }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        {{ formatTime(n.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div class="border-t border-gray-100 dark:border-gray-700">
                        <Link
                            :href="route('notifications.index')"
                            class="block px-4 py-2 text-center text-sm text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            {{ fallbackText('view_all', 'View all') }}
                        </Link>
                    </div>
                </div>
            </template>
        </Dropdown>
    </div>
</template>
