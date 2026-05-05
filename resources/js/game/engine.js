// Engine — núcleo PixiJS de Daino.
//
// Responsabilidades en Bloque 4:
//   - Crear la Application en `<div id="app">` o `document.body`.
//   - Apilar las 4 capas visuales del doc 06 §3 (bg, gameplay, particles, hud).
//   - Montar el Filter audio-reactivo sobre un sprite full-viewport en la
//     capa de fondo. En idle (sin AudioEngine atachado) el shader solo lee
//     uTime; al atachar audio empieza a alimentarse de FFT/RMS/bands.
//   - Pausar el ticker cuando el AudioContext esté `suspended` (doc 07 §C.3).
//   - Si `prefers-reduced-motion`, no instancia el filter — el body muestra
//     el degradado CSS estático y el canvas queda transparente sobre él.
//
// Se devuelve una API mínima para el resto del módulo: `attachAudio()` y
// `getCanvas()`. El upload.js llama a `attachAudio` tras el primer drop.

import {
    Application,
    Container,
    Sprite,
    Texture,
} from 'pixi.js';

import { createAudioFilter, FFT_BINS } from './shaders/audioFilter.js';
import { computeBands } from './audio/features.js';
import { createParticleLayer } from './entities/ParticleLayer.js';

const REDUCED_MOTION = matchMedia('(prefers-reduced-motion: reduce)').matches;
const DEBUG_MODE = (() => {
    const p = new URLSearchParams(window.location.search).get('debug');
    if (p === 'fft') return 1;
    if (p === 'uv') return 2;
    if (p === 'uniforms') return 3;
    return 0;
})();

let app = null;
let audio = null;          // AudioEngine atachado (o null)
let audioFilter = null;    // resultado de createAudioFilter()
let bgSprite = null;
let isPaused = false;
let timeAccum = 0;         // segundos desde init — siempre avanza salvo pausa

// Layers expuestos a module-scope para que `getApi().getLayers()` pueda
// devolver referencias estables sin filtrar internals (lo consume Bloque 5
// para añadir Dino/Obstacle al gameLayer y BitmapText al hudLayer).
let bgLayer = null;
let gameLayer = null;
let particleLayer = null;
let hudLayer = null;

/**
 * Inicializa Pixi y monta el escenario. Idempotente — segunda llamada es no-op.
 *
 * @param {HTMLElement} mountTarget  Dónde insertar el canvas (ej. document.body).
 */
export async function startEngine(mountTarget = document.body) {
    if (app !== null) return getApi();

    app = new Application();
    await app.init({
        resizeTo: window,
        autoDensity: true,
        resolution: window.devicePixelRatio || 1,
        preference: 'webgl',           // doc 09 §2.6 — WebGPU como toggle Bloque 8.
        antialias: false,
        powerPreference: 'high-performance',
        background: 0x05050a,
        backgroundAlpha: REDUCED_MOTION ? 0 : 1,
    });

    mountTarget.appendChild(app.canvas);  // OJO: app.canvas en v8 (no app.view).

    // Layers — 4 contenedores apilados, mismo orden que doc 06 §3.
    bgLayer       = new Container({ label: 'bg' });
    gameLayer     = new Container({ label: 'game' });
    particleLayer = createParticleLayer({
        width: window.innerWidth,
        height: window.innerHeight,
    });
    hudLayer      = new Container({ label: 'hud' });
    app.stage.addChild(bgLayer, gameLayer, particleLayer, hudLayer);

    // Si el user pidió reducir movimiento, no montamos el filter — el body
    // muestra el degradado CSS estático que ya define main.css. El canvas
    // queda transparente y los bloques siguientes pueden pintar gameplay
    // encima sin problemas.
    if (!REDUCED_MOTION) {
        audioFilter = createAudioFilter();

        // Sprite "vacío" (Texture.WHITE) que ocupa toda la pantalla — sirve de
        // soporte al Filter, que ignora uTexture y pinta desde cero. Tinta
        // negra para evitar flash blanco si el filtro tarda un frame en
        // aplicarse en el primer render.
        //
        // Dimensionado con window.innerWidth/Height (no app.screen) porque
        // tras `await app.init({ resizeTo: window })` el internal resize de
        // Pixi puede no haber corrido todavía — `app.screen` reporta los
        // defaults (800x600) y el sprite cubre solo media pantalla. window
        // sí está fiable inmediatamente.
        bgSprite = new Sprite(Texture.WHITE);
        bgSprite.tint = 0x000000;
        bgSprite.filters = [audioFilter.filter];
        bgLayer.addChild(bgSprite);

        const fitBg = () => {
            bgSprite.width = window.innerWidth;
            bgSprite.height = window.innerHeight;
        };
        fitBg();
        window.addEventListener('resize', fitBg);
    }

    // Pause/resume del ticker basado en el estado del AudioContext.
    // dispatch lo hace AudioEngine cuando recibe `onstatechange`.
    window.addEventListener('audio:pause', () => { isPaused = true; });
    window.addEventListener('audio:resume', () => { isPaused = false; });

    app.ticker.add(tick);

    return getApi();
}

function tick(ticker) {
    if (isPaused) return;
    if (!audioFilter) return;

    timeAccum += ticker.deltaMS / 1000;

    if (audio !== null) {
        // Audio activo — alimentar FFT real y bandas.
        const fftSlice = audio.getFrequencyData(FFT_BINS);
        audioFilter.fftBuffer.set(fftSlice);
        audioFilter.uploadFft();

        const bands = computeBands(fftSlice);
        const rms = audio.getRMS();
        audioFilter.setUniforms({
            time: timeAccum,
            rms,
            bass: bands.bass,
            mid: bands.mid,
            high: bands.high,
            debugMode: DEBUG_MODE,
        });
    } else {
        // Idle — solo uTime avanza. Las bandas y RMS quedan a 0. uFFT ya se
        // subió en createAudioFilter() con ceros, no hace falta re-subirlo.
        audioFilter.setUniforms({ time: timeAccum, debugMode: DEBUG_MODE });
    }
}

/**
 * Conecta un AudioEngine al ticker. Llamar tras el primer drop de MP3.
 * Idempotente — reemplaza el audio anterior si lo había.
 */
export function attachAudio(audioEngine) {
    audio = audioEngine;
}

export function detachAudio() {
    audio = null;
}

function getApi() {
    return {
        app,
        attachAudio,
        detachAudio,
        getCanvas: () => app?.canvas ?? null,
        // Bloque 5: la GameSession monta Dino/Obstacle en gameLayer y HUD en
        // hudLayer. Devolvemos refs vivas — no copiamos para evitar el
        // foot-gun de "monté en una copia y nada se ve".
        getLayers: () => ({ bgLayer, gameLayer, particleLayer, hudLayer }),
        // Passthrough del ticker. La GameSession registra su tick aquí; no
        // creamos un rAF propio (un solo loop, ya pausa con audio:pause).
        getTicker: () => app?.ticker ?? null,
        get reducedMotion() { return REDUCED_MOTION; },
    };
}
