// Constantes del game core. Único módulo donde se tocan números cuando el
// "feel" del juego necesita ajuste — HMR de este archivo recarga el módulo
// sin tirar el AudioContext ni la pestaña.
//
// Justificación de los valores: vienen del Daino legacy (juego.js:23-29, 405,
// 32, 41-42) como punto de partida que ya tenía un feel validado. El user
// puede afinar.

// --------------------------- Mundo / suelo ---------------------------

/** Píxeles desde el bottom del viewport hasta donde "pisa" el Dino. */
export const GROUND_OFFSET_PX = 22;

/** Aceleración hacia abajo en px/s² aplicada al Dino mientras está en el aire. */
export const GRAVITY = 2500;

/** Velocidad inicial (negativa = hacia arriba) que toma el Dino al saltar. */
export const JUMP_VELOCITY = 900;

// --------------------------- Dino ---------------------------

/** Tamaño visual del Dino en px. Cuadrado para el placeholder de Bloque 5. */
export const DINO_SIZE = { w: 84, h: 84 };

/** X fija en pantalla donde vive el Dino (no se mueve horizontalmente). */
export const DINO_X = 120;

/**
 * Padding del hitbox respecto a los bordes del rectángulo visual. Generoso
 * a la derecha (legacy 30px) — hace al juego más "perdonador" cuando el
 * Dino aterriza tarde sobre un cactus pequeño.
 */
export const DINO_HITBOX_PADDING = { top: 10, right: 30, bottom: 15, left: 20 };

// --------------------------- Obstáculos ---------------------------

/**
 * Catálogo de tipos de obstáculo. Tamaños vienen del legacy desktop (juego.css)
 * convertidos a unidades del nuevo viewport. El kind decide cuál se instancia.
 */
export const OBSTACLE_KIND = Object.freeze({
    CACTUS_SMALL: { w: 46, h: 96, color: 0x6abe45 },
    CACTUS_WIDE: { w: 98, h: 66, color: 0x4d9e34 },
});

/** Padding del hitbox del obstacle (más ajustado que el del Dino). */
export const OBSTACLE_HITBOX_PADDING = { top: 6, right: 6, bottom: 4, left: 6 };

// --------------------------- Velocidad por BPM ---------------------------

/**
 * Mapeo BPM → velocidad de scroll. Lineal entre los anclajes con clamp en
 * los extremos. Evita que canciones a 200 BPM se vuelvan injugables o que
 * canciones lentas se sientan estancadas.
 */
export const SPEED_BY_BPM = Object.freeze({
    /** BPM ancla bajo. */
    bpmLow: 60,
    /** Velocidad scroll en px/s para `bpmLow`. */
    speedLow: 300,
    /** BPM ancla alto. */
    bpmHigh: 180,
    /** Velocidad scroll en px/s para `bpmHigh`. */
    speedHigh: 540,
    /** Clamp inferior (px/s). */
    min: 250,
    /** Clamp superior (px/s). */
    max: 600,
});

/** Devuelve la velocidad de scroll en px/s para un BPM dado, clamped. */
export function speedFromBpm(bpm) {
    const { bpmLow, speedLow, bpmHigh, speedHigh, min, max } = SPEED_BY_BPM;
    const t = (bpm - bpmLow) / (bpmHigh - bpmLow);
    const speed = speedLow + t * (speedHigh - speedLow);
    return Math.max(min, Math.min(max, speed));
}

// --------------------------- Generación de nivel ---------------------------

/**
 * Tiempo mínimo y máximo entre obstáculos consecutivos en segundos. El
 * LevelGenerator descarta onsets que estén a menos de `min` del anterior
 * aceptado (para no amontonar) y rellena gaps mayores que `max` (para no
 * dejar tramos aburridos).
 */
export const SPAWN_WINDOW_S = Object.freeze({ min: 0.7, max: 1.8 });

/**
 * BPM de fallback si la autocorrelación del envelope no encuentra un pico
 * suficientemente fuerte (canciones sin pulso claro: ambient, drones, etc.).
 */
export const FALLBACK_BPM = 120;

/** Cap del deltaTime en segundos. Evita saltos al volver de background. */
export const DT_CAP_S = 1 / 30;

// --------------------------- HUD ---------------------------

export const HUD = Object.freeze({
    fontName: 'DainoHud',
    fontSize: 32,
    fontColor: 0xffffff,
    /** Caracteres precompilados para BitmapFont.install. */
    chars: '0123456789 :./-%SCOREgameovrtryain',
    /** Padding desde el borde del viewport. */
    padding: 24,
});
