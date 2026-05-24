/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: "class",
    content: ["./**/*.php"],
    theme: {
        extend: {
            colors: {
                primary: "#B8860B", // Tu color dorado
                "accent-gold": "#C5A059",
                "background-light": "#FFFFFF",
                "background-dark": "#121212",
            },
            fontFamily: {
                display: ["Playfair Display", "serif"],
                sans: ["Inter", "sans-serif"],
            },
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
}
