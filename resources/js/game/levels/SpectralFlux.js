// Onset detection vía Spectral Flux. Funciones puras — el LevelGenerator
// las invoca offline (antes de arrancar la partida) sobre el AudioBuffer
// completo. NO se ejecuta en runtime durante el gameplay.
//
// Por qué casero (FFT propio) y no OfflineAudioContext + AnalyserNode:
//   - AnalyserNode aplica smoothing fijo y promedia en bins log-escala —
//     bueno para visual, malo para detección precisa de transitorios.
//   - OfflineAudioContext renderiza en bloques internos que no se exponen
//     frame por frame; sacar magnitudes alineadas a hops específicos exige
//     un AudioWorklet, que añade complejidad de mensajería.
//   - Una FFT Cooley-Tukey iterativa son ~50 líneas y procesa un MP3 de
//     4min en <1s. Cero deps, determinista, alineable al hop exacto.
//
// Algoritmo (estándar de la literatura — "Spectral Flux", Dixon 2006):
//   1. Mix multi-canal a mono.
//   2. Por cada hop, ventana Hann sobre frame de N samples.
//   3. FFT real → magnitudes |X[k]|.
//   4. Flux[t] = Σ max(0, |X[k]|_t − |X[k]|_(t-1)) sobre todos los bins.
//   5. Normalizar el envelope (dividir por max).
//
// El resultado es un Float32Array de un valor por hop. Su sample rate
// efectivo es `audioBuffer.sampleRate / hopSize`.

const FRAME_SIZE = 2048;
const HOP_SIZE = 512;

/**
 * @typedef {Object} OnsetEnvelopeResult
 * @property {Float32Array} envelope     Onset strength por hop (normalizado [0,1]).
 * @property {number}       sampleRate   Sample rate del envelope (= audioRate / hopSize).
 * @property {number}       hopSize      Hop size usado en samples.
 * @property {number}       frameSize    Frame size usado en samples.
 */

/**
 * Calcula el envelope de onsets por Spectral Flux.
 *
 * @param {AudioBuffer} audioBuffer
 * @param {{ onProgress?: (ratio:number) => void }} [opts]
 * @returns {Promise<OnsetEnvelopeResult>}
 */
export async function computeOnsetEnvelope(audioBuffer, opts = {}) {
    const sampleRate = audioBuffer.sampleRate;
    const mono = mixToMono(audioBuffer);
    const hann = makeHannWindow(FRAME_SIZE);

    const numHops = Math.max(0, Math.floor((mono.length - FRAME_SIZE) / HOP_SIZE) + 1);
    const envelope = new Float32Array(numHops);

    // Reusable FFT scratch — no allocations dentro del loop.
    const re = new Float32Array(FRAME_SIZE);
    const im = new Float32Array(FRAME_SIZE);
    const halfBins = FRAME_SIZE / 2;
    // Doble buffer alternado: en cada iteración leemos de `prevMag` y
    // escribimos en `curMag`, luego hacemos swap. Así evitamos allocar.
    let prevMag = new Float32Array(halfBins);
    let curMag = new Float32Array(halfBins);

    let maxFlux = 0;
    // Yield al event loop cada YIELD_EVERY hops para no congelar UI durante
    // canciones largas. ~64 hops a hop=512 / sr=44100 ≈ 0.74s de audio por
    // chunk — el yield es imperceptible para el user.
    const YIELD_EVERY = 256;

    for (let h = 0; h < numHops; h++) {
        const offset = h * HOP_SIZE;
        for (let i = 0; i < FRAME_SIZE; i++) {
            re[i] = mono[offset + i] * hann[i];
            im[i] = 0;
        }
        fftRadix2(re, im);

        // Magnitudes (solo medio espectro — el resto es conjugado para
        // input real). Spectral Flux con half-wave rectification.
        let flux = 0;
        for (let k = 0; k < halfBins; k++) {
            const mag = Math.sqrt(re[k] * re[k] + im[k] * im[k]);
            curMag[k] = mag;
            const diff = mag - prevMag[k];
            if (diff > 0) flux += diff;
        }
        envelope[h] = flux;
        if (flux > maxFlux) maxFlux = flux;

        // Swap: cur pasa a ser prev en la próxima iteración. El array que
        // era prev queda libre para sobrescribirse como nuevo cur.
        const tmp = prevMag;
        prevMag = curMag;
        curMag = tmp;

        if ((h & (YIELD_EVERY - 1)) === 0 && h > 0) {
            if (opts.onProgress) opts.onProgress(h / numHops);
            await new Promise((r) => setTimeout(r, 0));
        }
    }

    // Normalizar a [0, 1] dividiendo por max.
    if (maxFlux > 0) {
        const inv = 1 / maxFlux;
        for (let i = 0; i < envelope.length; i++) envelope[i] *= inv;
    }

    if (opts.onProgress) opts.onProgress(1);

    // Yield final — cede al event loop para que la UI pinte el "Generando…"
    // si el caller lo necesita.
    await new Promise((r) => setTimeout(r, 0));

    return {
        envelope,
        sampleRate: sampleRate / HOP_SIZE,
        hopSize: HOP_SIZE,
        frameSize: FRAME_SIZE,
    };
}

/**
 * Picos locales del envelope sobre la mediana móvil. Devuelve timestamps en
 * segundos absolutos del audio original.
 *
 * @param {OnsetEnvelopeResult} env
 * @param {{ thresholdRatio?: number, windowSec?: number }} [opts]
 *   thresholdRatio  Multiplicador sobre la mediana móvil (default 1.5 — picos al menos 50% por encima de la mediana).
 *   windowSec       Tamaño de la ventana móvil para la mediana, en segundos (default 0.4).
 * @returns {number[]}  Timestamps en segundos de cada onset detectado.
 */
export function pickPeaks(env, opts = {}) {
    const { envelope, sampleRate } = env;
    const thresholdRatio = opts.thresholdRatio ?? 1.5;
    const windowSec = opts.windowSec ?? 0.4;
    const halfWindow = Math.max(1, Math.round((windowSec / 2) * sampleRate));

    const onsets = [];
    const buf = new Float32Array(2 * halfWindow + 1);

    for (let i = 1; i < envelope.length - 1; i++) {
        // Ventana móvil [i-half, i+half] clamp a bordes.
        const lo = Math.max(0, i - halfWindow);
        const hi = Math.min(envelope.length - 1, i + halfWindow);
        const n = hi - lo + 1;
        for (let j = 0; j < n; j++) buf[j] = envelope[lo + j];
        // Sort en sub-rango — O(N log N) pero N pequeño (~70 con sr ~86Hz, half 0.2s).
        const sub = buf.subarray(0, n).slice().sort();
        const median = sub[(n / 2) | 0];
        const threshold = median * thresholdRatio;

        const v = envelope[i];
        // Pico estricto local + por encima del umbral.
        if (v > threshold && v > envelope[i - 1] && v >= envelope[i + 1]) {
            onsets.push(i / sampleRate);
        }
    }
    return onsets;
}

// --------------------------- Helpers ---------------------------

/** Promedia todos los canales del buffer a un único Float32Array mono. */
function mixToMono(audioBuffer) {
    const ch = audioBuffer.numberOfChannels;
    const len = audioBuffer.length;
    if (ch === 1) return audioBuffer.getChannelData(0).slice();

    const out = new Float32Array(len);
    for (let c = 0; c < ch; c++) {
        const data = audioBuffer.getChannelData(c);
        for (let i = 0; i < len; i++) out[i] += data[i];
    }
    const inv = 1 / ch;
    for (let i = 0; i < len; i++) out[i] *= inv;
    return out;
}

/** Ventana Hann de N puntos. Reduce leakage en el FFT. */
function makeHannWindow(n) {
    const w = new Float32Array(n);
    const k = (2 * Math.PI) / (n - 1);
    for (let i = 0; i < n; i++) {
        w[i] = 0.5 * (1 - Math.cos(i * k));
    }
    return w;
}

/**
 * FFT Cooley-Tukey radix-2 iterativa, in-place. N debe ser potencia de 2.
 * Implementación estándar — sirve para magnitudes (no nos importa la fase).
 *
 * @param {Float32Array} re  Parte real (input/output).
 * @param {Float32Array} im  Parte imaginaria (input/output).
 */
function fftRadix2(re, im) {
    const n = re.length;
    if ((n & (n - 1)) !== 0) throw new Error(`FFT: n=${n} no es potencia de 2`);

    // Bit-reversal permutation.
    for (let i = 1, j = 0; i < n; i++) {
        let bit = n >> 1;
        for (; j & bit; bit >>= 1) j ^= bit;
        j ^= bit;
        if (i < j) {
            let tr = re[i]; re[i] = re[j]; re[j] = tr;
            tr = im[i]; im[i] = im[j]; im[j] = tr;
        }
    }

    // Cooley-Tukey butterflies.
    for (let size = 2; size <= n; size <<= 1) {
        const half = size >> 1;
        const tableStep = (-2 * Math.PI) / size;
        for (let i = 0; i < n; i += size) {
            for (let k = 0; k < half; k++) {
                const angle = tableStep * k;
                const wr = Math.cos(angle);
                const wi = Math.sin(angle);
                const ix = i + k;
                const jx = ix + half;
                const tre = re[jx] * wr - im[jx] * wi;
                const tim = re[jx] * wi + im[jx] * wr;
                re[jx] = re[ix] - tre;
                im[jx] = im[ix] - tim;
                re[ix] += tre;
                im[ix] += tim;
            }
        }
    }
}
