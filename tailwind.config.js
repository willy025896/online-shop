import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                brand: {
                    50: '#EEF2F6',
                    100: '#D7E1EA',
                    200: '#AFC3D5',
                    300: '#87A5C0',
                    400: '#4F739A',
                    500: '#1E3A5F',
                    600: '#1A3252',
                    700: '#152940',
                    800: '#101F31',
                    900: '#0B1521',
                },
                accent: {
                    50: '#FDF4EC',
                    100: '#FAE3CD',
                    200: '#F4C89C',
                    300: '#EFAD73',
                    400: '#E8985C',
                    500: '#DD8544',
                    600: '#D97F3D',
                    700: '#B4652F',
                    800: '#8A4D25',
                    900: '#5F351A',
                },
            },
            borderRadius: {
                xl: '0.875rem',
                '2xl': '1.25rem',
            },
            boxShadow: {
                soft: '0 2px 8px -2px rgba(11, 21, 33, 0.08), 0 1px 2px -1px rgba(11, 21, 33, 0.06)',
                lift: '0 12px 24px -8px rgba(11, 21, 33, 0.22), 0 4px 8px -2px rgba(11, 21, 33, 0.1)',
                glow: '0 8px 20px -6px rgba(232, 152, 92, 0.45)',
            },
            keyframes: {
                pop: {
                    '0%, 100%': { transform: 'scale(1)' },
                    '50%': { transform: 'scale(1.18)' },
                },
            },
            animation: {
                pop: 'pop 0.35s ease-in-out',
            },
        },
    },

    plugins: [forms, typography],
};
