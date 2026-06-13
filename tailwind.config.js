import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js,ts,jsx,tsx}',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            opacity: {
                '6': '0.06',
                '8': '0.08',
                '12': '0.12',
                '14': '0.14',
                '15': '0.15',
                '16': '0.16',
                '18': '0.18',
                '35': '0.35',
                '45': '0.45',
                '55': '0.55',
                '65': '0.65',
                '78': '0.78',
                '85': '0.85',
                '88': '0.88',
                '92': '0.92',
            },
        },
    },

    plugins: [forms],
};
