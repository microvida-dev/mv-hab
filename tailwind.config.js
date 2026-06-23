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
                    500: '#168c7a',
                    600: '#0f7466',
                    700: '#0d5d54',
                    900: '#123331',
                },
                ink: {
                    50: '#f7f8fa',
                    100: '#e9edf1',
                    500: '#647181',
                    700: '#334155',
                    900: '#18212f',
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
