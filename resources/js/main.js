// Daino — entry point del bundle.
//
// Responsabilidades:
//   1. Importar el CSS principal para que Vite lo procese y emita en manifest.
//   2. Activar el polyfill de modulepreload (doc 09 §1.3) — necesario en
//      integración backend porque el HTML lo emite PHP, no Vite.
//   3. Detectar modo (standalone vs iframe) y arrancar el bridge si aplica.
//   4. Iniciar el engine Pixi cuando el DOM esté listo.
//   5. Cablear el drag&drop sobre document.body para inyectar MP3s.
//   6. (Bloque 5) Al recibir `audio:ready`, parar la reproducción que
//      upload.js arrancó, generar la ObstacleTimeline (overlay visible),
//      reiniciar la canción desde 0 y arrancar la GameSession sincronizada.

import 'vite/modulepreload-polyfill';
import '@css/main.css';

import { startEngine } from '@/game/engine.js';
import { setupUpload } from '@/ui/upload.js';
import { startBridge } from '@/iframe/bridge.js';
import { generate } from '@/game/levels/LevelGenerator.js';
import { readSeedFromUrl } from '@/game/levels/Seed.js';
import { GameSession } from '@/game/GameSession.js';

let activeSession = null;
let booted = false;
let engineApi = null;

// Debug handle expuesto a window para inspección desde DevTools console.
// El doble underscore señala "debug-only, no API estable". Smoke 2 del
// Bloque 5 lo necesita para verificar determinismo de la timeline:
//   JSON.stringify(window.__daino.session.level.timeline.slice(0, 10))
// Si en algún momento esto sangra a producción, conviene gatear el set
// con `import.meta.env.DEV` — por ahora aceptamos la exposición porque
// no contiene PII ni tokens, solo state del juego.
if (typeof window !== 'undefined') {
    window.__daino = window.__daino ?? {};
}
function publishDebug() {
    if (typeof window === 'undefined') return;
    window.__daino.session = activeSession;
    window.__daino.engine = engineApi;
}

function showHintWithText(text) {
    const hint = document.getElementById('hint');
    if (!hint) return;
    hint.textContent = text;
    hint.classList.remove('is-hidden');
}

function hideHint() {
    const hint = document.getElementById('hint');
    if (!hint) return;
    hint.classList.add('is-hidden');
}

async function onAudioReady(ev) {
    const { audioBuffer, audioEngine, name } = ev.detail ?? {};
    if (!audioBuffer || !audioEngine) {
        console.warn('[daino] audio:ready sin audioBuffer/audioEngine — ignorado');
        return;
    }

    // 1. Despide la sesión anterior si existía (el user soltó otro MP3 sin
    //    pasar por game over). Liberar antes de tocar el audio.
    if (activeSession) {
        activeSession.stop();
        activeSession = null;
    }

    // 2. Parar la canción que `upload.js` arrancó automáticamente — la
    //    queremos sincronizada desde 0 con el primer tick de la GameSession.
    if (audioEngine.source) {
        try { audioEngine.source.stop(); } catch { /* idempotente */ }
    }

    // 3. Mostrar overlay "Generando nivel…" reusando #hint (evita meter más
    //    DOM nuevo). El user ve el progreso y entiende por qué hay un hueco.
    showHintWithText('Generando nivel…');

    let level;
    try {
        const seed = readSeedFromUrl();  // null en uso normal; ?seed=N para reproducir.
        level = await generate(audioBuffer, {
            seed: seed ?? undefined,
            sourceName: name ?? 'unknown.mp3',
            onProgress: (msg) => { showHintWithText(msg); },
        });
    } catch (err) {
        console.error('[daino] generate() failed:', err);
        showHintWithText('Error generando nivel — vuelve a soltar el MP3');
        return;
    }

    console.info('[daino] level ready:', {
        bpm: level.bpm.toFixed(1),
        gameSpeed: level.gameSpeed.toFixed(0),
        seed: level.seed,
        confidence: level.stats.confidence.toFixed(2),
        onsetsKept: level.stats.onsetsKept,
        durationSec: level.durationSec.toFixed(1),
    });

    hideHint();

    // 4. Reiniciar la canción desde 0 — ahora la GameSession leerá audioTime
    //    desde su primer tick alineado al play actual.
    audioEngine.play(audioBuffer);

    // 5. Arrancar la sesión.
    activeSession = new GameSession({
        engine: engineApi,
        audioEngine,
        level,
        onGameOver: ({ reason }) => {
            // El HUD ya muestra "GAME OVER" / "WIN". El #hint vuelve a aparecer
            // con instrucción para reintentar — soltar el mismo MP3 (o uno
            // nuevo) reinicia el ciclo.
            showHintWithText(reason === 'win'
                ? 'Suelta otro MP3 para seguir'
                : 'Suelta el MP3 otra vez para reintentar');
        },
    });
    activeSession.start();
    publishDebug();
}

async function boot() {
    if (booted) return;
    booted = true;

    const bridgeStatus = startBridge();
    if (bridgeStatus.embedded) {
        console.info('[daino] modo iframe — bridge activo');
    }

    const mount = document.getElementById('app') ?? document.body;
    engineApi = await startEngine(mount);

    setupUpload({ engine: engineApi });
    window.addEventListener('audio:ready', onAudioReady);

    publishDebug();

    if (engineApi.reducedMotion) {
        console.info('[daino] prefers-reduced-motion: shader desactivado, fallback CSS estático');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { void boot(); });
} else {
    void boot();
}
