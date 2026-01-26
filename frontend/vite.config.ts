import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import svgLoader from 'vite-svg-loader'

// https://vite.dev/config/
export default defineConfig({
  // Modifica qui sotto aggiungendo la configurazione
  plugins: [
    vue(),
    vueDevTools(),
    svgLoader({
      defaultImport: 'url', // Ora tutti gli SVG sono trattati come immagini normali di default
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
