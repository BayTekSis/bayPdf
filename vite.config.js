import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  base: './',
  publicDir: false,
  build: {
    outDir: 'public',
    emptyOutDir: false,
    rollupOptions: {
      input: 'resources/js/main.js',
      output: {
        entryFileNames: 'designer.js',
        assetFileNames: asset => asset.names?.some(name => name.endsWith('.css')) ? 'designer.css' : 'assets/[name]-[hash][extname]',
      },
    },
    sourcemap: false,
  },
})
