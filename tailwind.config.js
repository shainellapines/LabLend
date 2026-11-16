import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
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
            // LabLend palette (CLAUDE.md section 10.2).
            // primary-600 = #4F46E5 (indigo), accent-600 = #0D9488 (teal).
            colors: {
                primary: colors.indigo,
                accent: colors.teal,
                canvas: '#F8FAFC', // page background
                ink: '#0F172A',    // main text
                muted: '#64748B',  // secondary text
                line: '#E2E8F0',   // borders
            },
        },
    },

    plugins: [forms],
};
