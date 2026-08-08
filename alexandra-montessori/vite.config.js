import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
//
// Deployment target: the Hostinger subdomain alexandra.krildigital.com, where the
// built `dist/` is uploaded to the subdomain document root and served from `/`.
// So the production base must be '/': hashed entry assets, lazy route chunks and
// public assets (photos, video) all resolve from the site root. This also matches
// the live site and keeps client-side routing working on nested routes.
export default defineConfig(() => ({
  base: '/',
  plugins: [react()],
  server: {
    port: 5173,
    open: true,
    // Proxy WordPress REST calls to the Local WP site so the contact /
    // availability / application forms can be tested end-to-end from :5173.
    // (In production the app is served by WordPress, so this is same-origin.)
    proxy: {
      "/wp-json": {
        target: "https://alexandra-montessori.local",
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    // Emit .vite/manifest.json so the theme can enqueue the correct hashed
    // files without hardcoding them (survives every rebuild).
    manifest: true,
  },
}))
