// PRNG seeded — xorshift32. Determinismo es invariante del Bloque 5: dos
// runs con el mismo seed deben producir la misma ObstacleTimeline. Math.random
// no sirve porque no es semilla-able.
//
// xorshift32 es un PRNG de 32 bits con periodo 2^32 - 1. Suficiente calidad
// estadística para "elegir entre 2 kinds de cactus N veces". Si en el
// futuro se necesita más calidad (tirar con distribución no uniforme),
// upgradear a sfc32 o pcg32 — misma firma, drop-in.

/**
 * Hash 32-bit barato para convertir un seed numérico arbitrario (incluso
 * 0) en un estado inicial no degenerado para xorshift32. Si pasas seed=0
 * directo a xorshift, el generador queda atascado en 0.
 */
function mix32(x) {
    x = ((x | 0) + 0x9e3779b9) | 0;
    x = Math.imul(x ^ (x >>> 16), 0x85ebca6b);
    x = Math.imul(x ^ (x >>> 13), 0xc2b2ae35);
    x = x ^ (x >>> 16);
    return x | 0;
}

export class Seeded {
    /**
     * @param {number} [seed]  Si no se pasa, se genera con crypto. Guarda el
     *                         seed en `this.seed` para que el caller pueda
     *                         persistirlo (Bloque 7 lo pondrá en `levels.generator_seed`).
     */
    constructor(seed) {
        if (seed === undefined || seed === null) {
            const buf = new Uint32Array(1);
            crypto.getRandomValues(buf);
            seed = buf[0];
        }
        // Preservamos el seed original para reproducibilidad. El estado
        // mutable vive en _state (separado).
        this.seed = (seed | 0);
        this._state = mix32(this.seed) || 0x9e3779b9;  // jamás 0
    }

    /** Avanza el estado y devuelve un float en [0, 1). */
    next() {
        let x = this._state;
        x ^= x << 13;
        x ^= x >>> 17;
        x ^= x << 5;
        this._state = x | 0;
        // (>>> 0) convierte a unsigned 32-bit; / 2^32 lo lleva a [0, 1).
        return ((x | 0) >>> 0) / 4294967296;
    }

    /** Entero uniforme en [0, n). Útil para `prng.int(2)` → 0 o 1. */
    int(n) {
        return Math.floor(this.next() * n);
    }

    /** Picks aleatorio de un array (con uniforme). */
    pick(arr) {
        return arr[this.int(arr.length)];
    }
}

/**
 * Lee `?seed=N` de la URL. Util como debug toggle para test de determinismo
 * (smoke 2 del plan). Devuelve `null` si no está, NaN, o no es un entero.
 */
export function readSeedFromUrl() {
    if (typeof window === 'undefined') return null;
    const raw = new URLSearchParams(window.location.search).get('seed');
    if (raw === null) return null;
    const n = Number.parseInt(raw, 10);
    return Number.isFinite(n) ? n : null;
}
