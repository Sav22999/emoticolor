import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import svgLoader from 'vite-svg-loader'
import { VitePWA } from 'vite-plugin-pwa'
import packageJson from './package.json'

// https://vite.dev/config/
export default defineConfig({
  define: {
    __APP_VERSION__: JSON.stringify(packageJson.version),
  },
  plugins: [
    vue(),
    vueDevTools(),
    svgLoader({
      defaultImport: 'url', // 'url' | 'component'
    }),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'Emoticolor',
        short_name: 'Emoticolor',
        description: 'Il social network per condividere emozioni a colori, in sicurezza!',
        theme_color: '#269DFF', // Used on the browser UI
        background_color: '#269DFF', // Used on the splash screen
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/',
        lang: 'it',
        dir: 'ltr',
        categories: ['productivity', 'utilities'],
        shortcuts: [
          {
            name: 'Nuovo post',
            url: '/new-post',
          },
        ],
        icons: [
          {
            src: '/icon-192.png',
            sizes: '192x192',
            type: 'image/png',
          },
          {
            src: '/icon-512.png',
            sizes: '512x512',
            type: 'image/png',
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
