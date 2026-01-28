import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import svgLoader from 'vite-svg-loader'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  // Modifica qui sotto aggiungendo la configurazione
  plugins: [
    vue(),
    vueDevTools(),
    svgLoader({
      defaultImport: 'url', // Ora tutti gli SVG sono trattati come immagini normali di default
    }),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'Emoticolor',
        short_name: 'Emoticolor',
        description: 'Il social network per condividere emozioni a colori, in sicurezza!',
        theme_color: '#ffffff',
        background_color: '#ffffff',
        display: 'standalone',
        icons: [
          {
            src: '/assets/images/icon.svg',
            sizes: 'any',
            type: 'image/svg+xml',
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
