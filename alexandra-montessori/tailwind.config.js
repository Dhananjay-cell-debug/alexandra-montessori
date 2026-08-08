/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{js,jsx}"],
  theme: {
    extend: {
      colors: {
        cream: "#f5f1ef",
        sand: "#e9f1e6",
        sage: {
          50: "#f7faf5",
          100: "#eff5eb",
          200: "#dcead7",
          300: "#a3bc9a",
          400: "#aec7a5",
          500: "#a3bc9a",
          600: "#667c61",
          700: "#506457",
          800: "#405246",
          900: "#34443a",
        },
        olive: {
          DEFAULT: "#8f9b84",
          light: "#a9b59f",
          dark: "#7f8d76",
        },
        accent: {
          DEFAULT: "#a3bc9a",
          dark: "#a3bc9a",
        },
        ink: "#36463d",
      },
      fontFamily: {
        heading: ["Poppins", "system-ui", "Helvetica", "Arial", "sans-serif"],
        body: [
          "Helvetica",
          '"Helvetica Neue"',
          "Arial",
          "system-ui",
          "sans-serif",
        ],
        script: ["Poppins", "system-ui", "Helvetica", "Arial", "sans-serif"],
      },
      borderRadius: {
        "4xl": "0.85rem",
        "5xl": "1.1rem",
      },
      maxWidth: {
        content: "1200px",
        wide: "1320px",
      },
      boxShadow: {
        soft: "0 8px 24px -14px rgba(47, 51, 38, 0.20)",
        card: "0 14px 36px -18px rgba(47, 51, 38, 0.22)",
        glow: "0 0 0 1px rgba(107, 142, 78, 0.15)",
        nav: "0 6px 24px -10px rgba(47, 51, 38, 0.24)",
      },
      keyframes: {
        "fade-up": {
          "0%": { opacity: "0", transform: "translateY(18px)" },
          "100%": { opacity: "1", transform: "translateY(0)" },
        },
        float: {
          "0%, 100%": { transform: "translateY(0)" },
          "50%": { transform: "translateY(-10px)" },
        },
        "float-slow": {
          "0%, 100%": { transform: "translateY(0) rotate(0deg)" },
          "50%": { transform: "translateY(-14px) rotate(4deg)" },
        },
        marquee: {
          "0%": { transform: "translateX(0)" },
          "100%": { transform: "translateX(-50%)" },
        },
        "bounce-soft": {
          "0%, 100%": { transform: "translateY(0)" },
          "50%": { transform: "translateY(8px)" },
        },
      },
      animation: {
        "fade-up": "fade-up 1.1s cubic-bezier(0.22, 1, 0.36, 1) forwards",
        float: "float 8s ease-in-out infinite",
        "float-slow": "float-slow 12s ease-in-out infinite",
        marquee: "marquee 36s linear infinite",
        "bounce-soft": "bounce-soft 3.2s ease-in-out infinite",
      },
    },
  },
  plugins: [],
};
