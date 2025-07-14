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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                white: {
                    DEFAULT: '#FCFCFC',
                },
                gray: {
                    DEFAULT: '#54595F',
                    light: '#A0A5AB',
                },
                green: {
                    DEFAULT: '#7EAF67',
                    lightest: '#BEDDAF',
                    dark: '#4D723C',
                },
                yellow: {
                    DEFAULT: '#ECC24A',
                },
            },
        },
    },

    plugins: [forms],
};
