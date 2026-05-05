// Detección de BPM por autocorrelación del envelope de onsets.
//
// Idea: si una canción tiene tempo T BPM, el envelope (curva de "fuerza
// percusiva por instante") tiene auto-similitud al desfasarlo 60/T segundos.
// Buscamos el lag dentro del rango BPM válido (60–200) cuya correlación
// con el envelope original es máxima — ese lag corresponde al periodo del
// pulso. BPM = 60 / lag_segundos.
//
// Limitaciones:
//   - Confunde 60 BPM con 120 BPM y 90 con 180 (multiplicaciones del periodo).
//     Mitigamos prefiriendo el rango [80, 160] cuando la correlación entre el
//     pico encontrado y su mitad/doble es similar (sesgo a tempos comunes).
//   - Canciones sin pulso claro (ambient, drones) dan correlación débil.
//     Devolvemos `FALLBACK_BPM` (config) para esos casos.
//
// Es offline y O(N·L) donde N=longitud del envelope y L=tamaño del rango
// de lags. Para una canción de 4 min con sr_env ~86Hz: ~21k samples × ~70
// lags candidatos = 1.5M operaciones, <50ms en navegador moderno.

import { FALLBACK_BPM } from '../config.js';

const BPM_MIN = 60;
const BPM_MAX = 200;
/** Si la correlación normalizada del pico está por debajo de esto, fallback. */
const MIN_CORRELATION = 0.10;

/**
 * @param {{ envelope: Float32Array, sampleRate: number }} env
 * @returns {{ bpm: number, confidence: number, lagFrames: number }}
 */
export function detectBpm(env) {
    const { envelope, sampleRate: envSr } = env;
    if (envelope.length < envSr * 2) {
        // Menos de 2 segundos de envelope — no fiable.
        return { bpm: FALLBACK_BPM, confidence: 0, lagFrames: 0 };
    }

    const lagMin = Math.max(1, Math.floor((60 / BPM_MAX) * envSr));   // periodo más corto
    const lagMax = Math.min(envelope.length - 1, Math.ceil((60 / BPM_MIN) * envSr)); // periodo más largo

    // Correlación al lag 0 = energía del envelope. Sirve para normalizar.
    let energy = 0;
    for (let t = 0; t < envelope.length; t++) energy += envelope[t] * envelope[t];
    if (energy === 0) return { bpm: FALLBACK_BPM, confidence: 0, lagFrames: 0 };

    let bestLag = 0;
    let bestCorr = -Infinity;
    const corrByLag = new Float32Array(lagMax + 1);

    for (let lag = lagMin; lag <= lagMax; lag++) {
        let corr = 0;
        const limit = envelope.length - lag;
        for (let t = 0; t < limit; t++) {
            corr += envelope[t] * envelope[t + lag];
        }
        // Normalizar por la cantidad de pares — evita que lags grandes
        // (menos pares) compitan en desventaja artificial.
        const normalized = corr / (limit * energy / envelope.length);
        corrByLag[lag] = normalized;
        if (normalized > bestCorr) {
            bestCorr = normalized;
            bestLag = lag;
        }
    }

    if (bestCorr < MIN_CORRELATION) {
        return { bpm: FALLBACK_BPM, confidence: bestCorr, lagFrames: bestLag };
    }

    // Sesgo a tempos comunes: si encontré BPM bajo (<80) y existe pico
    // similar al doble, prefiero el doble (= mitad del lag). Y viceversa
    // si encontré BPM alto (>160) y la mitad del tempo (lag doble) tiene
    // correlación comparable.
    let lag = bestLag;
    let bpm = 60 / (lag / envSr);

    if (bpm < 80) {
        const halfLag = (bestLag / 2) | 0;
        if (halfLag >= lagMin && corrByLag[halfLag] > bestCorr * 0.85) {
            lag = halfLag;
            bpm = 60 / (lag / envSr);
        }
    } else if (bpm > 160) {
        const doubleLag = bestLag * 2;
        if (doubleLag <= lagMax && corrByLag[doubleLag] > bestCorr * 0.85) {
            lag = doubleLag;
            bpm = 60 / (lag / envSr);
        }
    }

    return { bpm, confidence: bestCorr, lagFrames: lag };
}
