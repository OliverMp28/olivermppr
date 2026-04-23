import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));

// Vite actúa como pipeline de assets para un backend PHP.
// - `dev`:   sirve HMR en localhost:5173; public/index.php lee VITE_DEV_SERVER
//            del .env y embebe los módulos desde esa URL.
// - `build`: compila a public/build/ con manifest.json; public/index.php lo
//            lee y emite <script>/<link> con los filenames hasheados.
//
// El PHP nunca necesita saber que Vite existe más allá de leer ese manifest.

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],

  // NO definimos `base`: en dev queremos que Vite sirva en la raíz de :5173
  // (para que `http://localhost:5173/resources/js/main.js` funcione tal cual
  // lo emite ViteAssets.php). En producción, ViteAssets ya antepone `/build/`
  // manualmente al leer el manifest — así que tampoco hace falta aquí.

  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    // CORS permisivo para localhost en cualquier puerto (dev únicamente).
    // Necesario porque los <script type="module"> cross-origin requieren
    // Access-Control-Allow-Origin y el puerto del APP_PORT puede variar.
    cors: {
      origin: /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/,
    },
  },

  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: 'manifest.json',
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'resources/js/main.js'),
        styles: resolve(__dirname, 'resources/css/main.css'),
      },
    },
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
      '@css': resolve(__dirname, 'resources/css'),
    },
  },
});
