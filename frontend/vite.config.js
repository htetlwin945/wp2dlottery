import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    // generate manifest.json in outDir
    manifest: true,
    rollupOptions: {
      // overwrite default .html entry
      input: 'src/main.js',
    },
    // outDir is relative to the project root
    outDir: 'dist',
  },
  resolve: {
    alias: {
      '@': '/src',
    },
  },
});