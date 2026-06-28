import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                civic: {
                    50: '#eefaf8',
                    100: '#d4f1ec',
                    200: '#a9e3da',
                    500: '#168c7a',
                    600: '#0f7466',
                    700: '#0d5d54',
                    800: '#0f4c45',
                    900: '#123331',
                },
                ink: {
                    50: '#f7f8fa',
                    100: '#e9edf1',
                    200: '#d8dee6',
                    300: '#b8c2cf',
                    400: '#8996a6',
                    500: '#647181',
                    600: '#465466',
                    700: '#334155',
                    800: '#253244',
                    900: '#18212f',
                    950: '#0f1722',
                },
                signal: {
                    50: '#fff7ed',
                    500: '#d97706',
                    700: '#92400e',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                surface: '0 1px 2px rgb(15 23 42 / 0.06)',
            },
        },
    },

    plugins: [forms],
};
