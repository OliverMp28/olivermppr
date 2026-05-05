// Extracción mínima de features sobre el array FFT.
//
// Bloque 4: solo división en bandas (bass / mid / high) y promedio. Suficiente
// para alimentar el shader de fondo. Bloque 5 añade Spectral Flux y onset
// detection para precomputar la ObstacleTimeline; eso vivirá en otro módulo
// para no contaminar la API de runtime (este `computeBands` se llama 60 veces
// por segundo y debe ser O(n) sin allocations).

const BAND_BASS_END = 10;    // hasta ~430 Hz aprox con fftSize=2048 @ 44.1kHz
const BAND_MID_END  = 100;   // hasta ~4300 Hz
// El resto hasta el final del buffer son "high".

/**
 * @param {Uint8Array} fft  Output de AnalyserNode.getByteFrequencyData (256 bins
 *                          tras downsample, valores 0..255).
 * @returns {{ bass: number, mid: number, high: number }}  Cada banda en [0,1].
 */
export function computeBands(fft) {
    const len = fft.length;
    let bassSum = 0;
    let midSum = 0;
    let highSum = 0;

    const bassEnd = Math.min(BAND_BASS_END, len);
    const midEnd  = Math.min(BAND_MID_END, len);

    for (let i = 0; i < bassEnd; i++) bassSum += fft[i];
    for (let i = bassEnd; i < midEnd; i++) midSum += fft[i];
    for (let i = midEnd; i < len; i++) highSum += fft[i];

    const bassN = bassEnd;
    const midN  = midEnd - bassEnd;
    const highN = len - midEnd;

    return {
        bass: bassN > 0 ? (bassSum / bassN) / 255 : 0,
        mid:  midN  > 0 ? (midSum  / midN)  / 255 : 0,
        high: highN > 0 ? (highSum / highN) / 255 : 0,
    };
}
