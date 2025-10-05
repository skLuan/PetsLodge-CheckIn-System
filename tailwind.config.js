import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**.js',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                nun: ['Nunito', 'Sans-serif'],
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                white: {
                    DEFAULT: '#FCFCFC',
                    yellow: '#FFFBEB',
                },
                gray: {
                    DEFAULT: '#54595F',
                    light: '#A0A5AB',
                    lightest: '#ECEDEE',
                },
                green: {
                    DEFAULT: '#7EAF67',
                    lightest: '#D7E2D1',
                    dark: '#4D723C',
                    '30': 'rgba(126, 175, 103, 0.30)',
                },
                yellow: {
                    DEFAULT: '#ECC24A',
                    second: '#E8C666',
                    
                },
                brown: {
                    DEFAULT: '#AC8464',
                    dark: '#644A35',
                },
            },
        },
    },

    plugins: [forms],
};
