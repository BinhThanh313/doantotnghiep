import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss()
  ],

  // Sửa base cho khớp với WampServer
  base: '/doantotnghiep/public/admin/',

  build: {
    outDir: '../public/admin',
    emptyOutDir: true,
  },

  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },

  server: {
    port: 5173,
    proxy: {
      '/api': {
        // Sửa target sang WampServer thay vì localhost:8000
        target: 'http://localhost/doantotnghiep/public',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://localhost/doantotnghiep/public',
        changeOrigin: true,
      },
    },
  },
})