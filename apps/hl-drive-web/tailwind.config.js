import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './app.vue',
        './layouts/**/*.vue',
        './plugins/**/*.{js,ts}',
        './*.{vue,js,ts,jsx,tsx,html}',
        './pages/**/*.{vue,js,ts,jsx,tsx}',
        './components/**/*.{vue,js,ts,jsx,tsx}',
        './node_modules/tw-elements/js/**/*.js',
    ],

    /** @see https://v3.tailwindcss.com/docs/dark-mode */
    // darkMode: 'selector',
    darkMode: ['variant', '&:not(.light *)'],

    theme: {
        extend: {
            //
        },
    },

    plugins: [forms],
};
