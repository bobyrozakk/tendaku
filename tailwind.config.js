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
                sans: ['Plus Jakarta Sans', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Exact palette tokens provided by user
                tendaku: {
                    wheat: '#F9E3B6',
                    sunglow: '#FBCE6B',
                    goldenrod: '#D5A007',
                    avocado: '#6C8B08',
                    brown: '#2B2202',
                    dark: '#2B2202',
                },
                // Full tonal shade scales for versatile UI components
                wheat: {
                    50: '#FDFCF7',
                    100: '#FAF6ED',
                    200: '#F6ECCF',
                    300: '#F9E3B6', // Base
                    400: '#F4D392',
                    500: '#E8BE65',
                    600: '#CA9E3C',
                    700: '#A17A24',
                    800: '#7A5C1B',
                    900: '#523E12',
                },
                sunglow: {
                    50: '#FFFBF0',
                    100: '#FEF5DB',
                    200: '#FDE8B3',
                    300: '#FBCE6B', // Base
                    400: '#F5B63E',
                    500: '#E59C1B',
                    600: '#C3780D',
                    700: '#97540B',
                    800: '#78400E',
                    900: '#5E320E',
                },
                goldenrod: {
                    50: '#FEFBEA',
                    100: '#FCF6C5',
                    200: '#F9EB8D',
                    300: '#F2D84C',
                    400: '#E5BD1B',
                    500: '#D5A007', // Base
                    600: '#B37B04',
                    700: '#8E5706',
                    800: '#75440C',
                    900: '#62370E',
                },
                avocado: {
                    50: '#F4F7E6',
                    100: '#E7EDC5',
                    200: '#D0DE8F',
                    300: '#B3CB53',
                    400: '#90B123',
                    500: '#6C8B08', // Base
                    600: '#546E06',
                    700: '#3F5306',
                    800: '#324208',
                    900: '#2A370A',
                },
                darkbrown: {
                    50: '#F9F8F5',
                    100: '#EFECE3',
                    200: '#DCD6C4',
                    300: '#BCB092',
                    400: '#8E7E59',
                    500: '#68593A',
                    600: '#4F4228',
                    700: '#3C311B',
                    800: '#2B2202', // Base
                    900: '#1B1501',
                    950: '#0F0C01',
                },
                // Semantic brand aliases
                brand: {
                    primary: {
                        DEFAULT: '#6C8B08', // Avocado
                        hover: '#546E06',
                        light: '#F4F7E6',
                        dark: '#3F5306',
                    },
                    accent: {
                        DEFAULT: '#D5A007', // Goldenrod
                        hover: '#B37B04',
                        light: '#FEFBEA',
                        dark: '#8E5706',
                    },
                    highlight: {
                        DEFAULT: '#FBCE6B', // Sunglow
                        hover: '#F5B63E',
                        light: '#FEF5DB',
                    },
                    surface: {
                        DEFAULT: '#F9E3B6', // Wheat
                        light: '#FAF6ED',
                        subtle: '#FDFCF7',
                    },
                    dark: {
                        DEFAULT: '#2B2202', // Drab Dark Brown
                        surface: '#3C311B',
                        card: '#241D02',
                    },
                },
            },
            boxShadow: {
                'tendaku-sm': '0 2px 8px -1px rgba(43, 34, 2, 0.06), 0 1px 4px -1px rgba(43, 34, 2, 0.04)',
                'tendaku': '0 4px 20px -2px rgba(43, 34, 2, 0.08), 0 2px 6px -2px rgba(43, 34, 2, 0.04)',
                'tendaku-lg': '0 12px 32px -4px rgba(43, 34, 2, 0.12), 0 4px 12px -2px rgba(43, 34, 2, 0.06)',
                'tendaku-glow': '0 0 24px -2px rgba(213, 160, 7, 0.35)',
                'tendaku-avocado-glow': '0 0 24px -2px rgba(108, 139, 8, 0.35)',
            },
        },
    },

    plugins: [forms],
};
