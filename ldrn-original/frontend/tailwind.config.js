/** @type {import('tailwindcss').Config} */

// Little Rainbows Day Nursery design tokens.
// Navy `ink` with a sky-blue scale and the six rainbow accents from the LDRN
// brand artwork. `band` colours resolve through CSS variables so the site can
// switch between the restrained and faithful rainbow themes at runtime.
export default {
  content: ["./index.html", "./src/**/*.{js,jsx}"],
  theme: {
    extend: {
      colors: {
        ink: "#213472",
        navy: "#213472",
        band: "var(--ldrn-band)",
        "band-strong": "var(--ldrn-band-strong)",
        sky: {
          50: "#f4fbfe",
          100: "#e4f4fb",
          200: "#cfecf8",
          300: "#bbe8f6",
          400: "#8fd6ef",
          500: "#4692d8",
          600: "#3b7cbb",
          700: "#2f5f98",
          800: "#274a7e",
          900: "#213472",
        },
        rainbow: {
          red: "#e43944",
          orange: "#fa9d27",
          yellow: "#feda30",
          green: "#279459",
          blue: "#2c6ed5",
          violet: "#6422af",
        },
      },
      opacity: {
        // The parent-stories band authors its scrim as `bg-sky-900/78`. That
        // step is not in Tailwind's default scale, so without this the rule
        // is never generated and the white heading sits unreadably on the
        // bare photograph — which is what the current live build does.
        78: "0.78",
      },
      fontFamily: {
        heading: ["Fraunces", "Georgia", "Palatino", "serif"],
        body: [
          "Nunito Sans",
          "system-ui",
          "-apple-system",
          "sans-serif",
        ],
      },
      borderRadius: {
        "4xl": "0.85rem",
        "5xl": "1.1rem",
      },
      maxWidth: {
        content: "1200px",
        wide: "1200px",
      },
      boxShadow: {
        soft: "0 8px 24px -14px rgba(33, 52, 114, 0.20)",
        card: "0 14px 36px -18px rgba(33, 52, 114, 0.22)",
        nav: "0 6px 24px -10px rgba(33, 52, 114, 0.24)",
      },
      keyframes: {
        "testimonial-in": {
          "0%": { opacity: "0", transform: "translateY(6px)" },
          "100%": { opacity: "1", transform: "translateY(0)" },
        },
      },
      animation: {
        "testimonial-in": "testimonial-in 0.5s ease-out forwards",
      },
    },
  },
  plugins: [],
};
