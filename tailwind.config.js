import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Space Grotesk', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                arabic: ['Cairo', 'Tajawal', 'IBM Plex Sans Arabic', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                /* Light-blue ramp: signature cyber accent (#38bdf8), azure at 600 for white-text contrast */
                blue: {
                    50: '#e8f7ff',
                    100: '#d0efff',
                    200: '#a8e3ff',
                    300: '#70d4ff',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                    950: '#082f49',
                },
                vault: {
                    50: '#e8f7ff',
                    100: '#d0efff',
                    200: '#a8e3ff',
                    300: '#70d4ff',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                    950: '#082f49',
                },
                cyber: {
                    black: '#000000',
                    dark: '#0a1019',
                    deeper: '#020409',
                    surface: '#0b1120',
                    elevated: '#111c31',
                    border: '#1b2740',
                    glow: '#38bdf8',
                },
                navy: {
                    50: '#f0f9ff',
                    100: '#e0f4ff',
                    600: '#075985',
                    700: '#0c4a6e',
                    800: '#082f49',
                    900: '#000000',
                },
            },
            backgroundImage: {
                'cyber-grid': 'linear-gradient(rgba(56,189,248,0.035) 1px, transparent 1px), linear-gradient(90deg, rgba(56,189,248,0.035) 1px, transparent 1px)',
                'cyber-radial': 'radial-gradient(ellipse at 50% 0%, rgba(56,189,248,0.09) 0%, transparent 60%)',
            },
            boxShadow: {
                'glow': '0 0 20px rgba(56,189,248,0.15)',
                'glow-lg': '0 0 40px rgba(56,189,248,0.28)',
                'glow-sm': '0 0 12px rgba(56,189,248,0.12)',
                'glass': '0 8px 32px rgba(0,0,0,0.3)',
                'glass-sm': '0 4px 16px rgba(0,0,0,0.2)',
            },
            backdropBlur: {
                glass: '16px',
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'slide-up': 'slideUp 0.4s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
                'shimmer': 'shimmer 2s linear infinite',
            },
            keyframes: {
                fadeIn: {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                slideUp: {
                    from: { opacity: '0', transform: 'translateY(12px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                slideDown: {
                    from: { opacity: '0', transform: 'translateY(-8px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                scaleIn: {
                    from: { opacity: '0', transform: 'scale(0.95)' },
                    to: { opacity: '1', transform: 'scale(1)' },
                },
                pulseGlow: {
                    '0%, 100%': { boxShadow: '0 0 20px rgba(56,189,248,0.15)' },
                    '50%': { boxShadow: '0 0 30px rgba(56,189,248,0.35)' },
                },
                shimmer: {
                    from: { backgroundPosition: '-200% 0' },
                    to: { backgroundPosition: '200% 0' },
                },
            },
        },
    },

    plugins: [forms],
};
