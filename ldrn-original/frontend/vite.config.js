import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath } from 'node:url'

// Little Rainbows Day Nursery frontend.
//
// `npm run build` produces the standalone static preview served from the site
// root. `npm run build:wp` re-bases the same source onto the LDRN WordPress
// theme so the theme can enqueue hashed assets from its own dist/ folder.
// LDRN has its own hosting and WordPress install; nothing here points at
// Alexandra Montessori.
export default defineConfig(() => ({
  base: '/',
  plugins: [react({ jsxImportSource: '@ldrn/editable' })],
  resolve: { alias: {
    '@ldrn/editable/jsx-runtime': fileURLToPath(new URL('./src/lib/editable-jsx-runtime.js', import.meta.url)),
    '@ldrn/editable/jsx-dev-runtime': fileURLToPath(new URL('./src/lib/editable-jsx-dev-runtime.js', import.meta.url)),
  } },
  server: {
    port: 5174,
    open: true,
    // Proxy REST calls to the local LDRN WordPress site so the contact,
    // availability and application forms can be exercised end to end from the
    // dev server. In production WordPress serves the app, so this is
    // same-origin.
    proxy: {
      '/wp-json': {
        target: 'https://ldrn.local',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    // Emit .vite/manifest.json so the theme resolves hashed entry files
    // without hardcoding build hashes.
    manifest: true,
  },
}))
