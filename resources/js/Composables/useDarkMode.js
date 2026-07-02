import { ref } from 'vue';

// The inline script in resources/views/app.blade.php already resolves localStorage/
// prefers-color-scheme and applies the `dark` class before Vue mounts (to avoid a
// flash of the wrong theme), so we just read the class it already set instead of
// redoing that resolution here. Keep STORAGE_KEY in sync with that script.
const STORAGE_KEY = 'theme';

const isDark = ref(typeof window !== 'undefined'
    ? document.documentElement.classList.contains('dark')
    : false);

export function useDarkMode() {
    const toggle = () => {
        isDark.value = !isDark.value;
        localStorage.setItem(STORAGE_KEY, isDark.value ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', isDark.value);
    };

    return { isDark, toggle };
}
