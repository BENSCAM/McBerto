import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#FEF1F1',
                    100: '#FDDEDE',
                    200: '#FABCBC',
                    300: '#F78D8D',
                    400: '#F24A4A',
                    500: '#EF1A1A',
                    600: '#D80F0F',
                    700: '#A20B0B',
                    800: '#810909',
                    900: '#5F0707',
                    950: '#390404',
                },
            },
        },
    },

    plugins: [forms],
};
