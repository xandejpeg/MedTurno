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
                // Escalas remapeadas para a identidade MedTurn
                teal: {
                    50: '#E6FBFA',
                    100: '#C6F5F2',
                    200: '#8FEAE5',
                    300: '#4FDAD2',
                    400: '#1BC9BF',
                    500: '#02BBB1', // Azul frente oficial
                    600: '#029A92',
                    700: '#037C76',
                    800: '#06605C',
                    900: '#084D4A',
                    950: '#01282B', // Azul fundo oficial
                },
                lime: {
                    50: '#F3FBEA',
                    100: '#E4F6D2',
                    200: '#C9ECA6',
                    300: '#A8DF73',
                    400: '#88D145',
                    500: '#6BC320', // Verde oficial
                    600: '#57A319',
                    700: '#438014',
                    800: '#356312',
                    900: '#2C5013',
                    950: '#152B07',
                },
                brand: {
                    teal: '#02BBB1',
                    'teal-dark': '#019A92',
                    'teal-soft': '#E0F7F5',
                    dark: '#01282B',
                    'dark-soft': '#0A3A3E',
                    green: '#6BC320',
                    'green-dark': '#57A319',
                    'green-soft': '#EEF9E3',
                },
            },
        },
    },

    plugins: [forms],
};
