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
            // LabLend palette (CLAUDE.md section 10.2).
            colors: {
                // Azure scale built around Dark Azure #00638E (600) and Light Azure #BFD8E3 (200).
                primary: {
                    50: '#EEF6FA',
                    100: '#DCECF3',
                    200: '#BFD8E3', // Light Azure
                    300: '#8CB9CC', // gradient end
                    400: '#4E95B3',
                    500: '#1E7AA1',
                    600: '#00638E', // Dark Azure: buttons, links, active nav
                    700: '#00557A',
                    800: '#004A6B', // gradient middle
                    900: '#00364F',
                    950: '#001F2E',
                },
                onyx: '#141414',     // sidebar background
                charcoal: '#2B2B2B', // sidebar hover / borders on dark
                canvas: '#F4F7F9',   // page background (a very light azure-grey)
                ink: '#141414',      // main text
                muted: '#64748B',    // secondary text
                line: '#E3E9ED',     // borders on light surfaces
            },
            backgroundImage: {
                // Branding gradient: #000000 → #004A6B → #00638E → #8CB9CC
                brand: 'linear-gradient(120deg, #000000 0%, #004A6B 45%, #00638E 70%, #8CB9CC 100%)',
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(20 20 20 / 0.04), 0 1px 3px 0 rgb(20 20 20 / 0.04)',
            },
        },
    },

    plugins: [forms],
};
