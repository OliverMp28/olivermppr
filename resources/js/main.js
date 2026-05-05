// Daino — entry point del bundle.
//
// Responsabilidades:
//   1. Importar el CSS principal para que Vite lo procese y emita en manifest.
//   2. Activar el polyfill de modulepreload (doc 09 §1.3) — necesario en
//      integración backend porque el HTML lo emite PHP, no Vite.
//   3. Detectar modo (standalone vs iframe) y arrancar el bridge si aplica.
//   4. Iniciar el engine Pixi cuando el DOM esté listo.
//   5. Cablear el drag&drop sobre document.body para inyectar MP3s.

import 'vite/modulepreload-polyfill';
import '@css/main.css';

import { startEngine } from '@/game/engine.js';
import { setupUpload } from '@/ui/upload.js';
import { startBridge } from '@/iframe/bridge.js';

async function boot() {
    // Bridge primero — si estamos en iframe necesita postear READY cuanto
    // antes para que el parent Vout responda con AUTH_TOKEN. En standalone
    // es un no-op (lo decide bridge.js internamente).
    const bridgeStatus = startBridge();
    if (bridgeStatus.embedded) {
        console.info('[daino] modo iframe — bridge activo');
    }

    const mount = document.getElementById('app') ?? document.body;
    const engine = await startEngine(mount);

    setupUpload({ engine });

    if (engine.reducedMotion) {
        console.info('[daino] prefers-reduced-motion: shader desactivado, fallback CSS estático');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { void boot(); });
} else {
    void boot();
}
