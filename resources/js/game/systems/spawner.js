// Spawner — cursor sobre la ObstacleTimeline pre-computada. Cero análisis
// de audio en runtime: solo recibe `audioTime` (segundos de canción) y mira
// si el siguiente obstáculo de la timeline ya entra en escena.
//
// Invariante (doc 08 §"Bloque 5" verificación 3): este módulo NO importa el
// AudioEngine. Solo recibe parámetros. Eso permite probar la timeline en
// aislamiento y mantiene la separación generación-offline / consumo-online.

import { Obstacle } from '../entities/Obstacle.js';

/**
 * @param {object} cfg
 * @param {Array<{tAt:number, kind:string}>} cfg.timeline
 * @param {{ addChild: Function, removeChild: Function }} cfg.gameLayer
 * @param {number} cfg.viewportWidth
 * @param {number} cfg.groundY
 * @returns {{
 *   tick: (audioTime:number, dt:number, gameSpeed:number) => void,
 *   getActive: () => Obstacle[],
 *   dispose: () => void,
 * }}
 */
export function createSpawner(cfg) {
    const { timeline, gameLayer, viewportWidth, groundY } = cfg;

    /** Índice del próximo obstáculo a instanciar. Avanza monótono. */
    let cursor = 0;
    /** @type {Obstacle[]} */
    const active = [];

    /** Spawna en el borde derecho del viewport + margen, para que entre suave. */
    const SPAWN_OFFSET_PX = 50;
    const spawnX = viewportWidth + SPAWN_OFFSET_PX;

    function spawnDue(audioTime) {
        while (cursor < timeline.length && timeline[cursor].tAt <= audioTime) {
            const entry = timeline[cursor];
            const o = new Obstacle(entry.kind, spawnX, groundY);
            gameLayer.addChild(o);
            active.push(o);
            cursor++;
        }
    }

    function moveAndCullActive(dt, gameSpeed) {
        // Iteración hacia atrás para hacer splice sin perder índices — patrón
        // del legacy (juego.js:436-445), también aplicable aquí.
        for (let i = active.length - 1; i >= 0; i--) {
            const o = active[i];
            o.update(dt, gameSpeed);
            if (o.isOffscreenLeft()) {
                gameLayer.removeChild(o);
                o.destroy({ children: true });
                active.splice(i, 1);
            }
        }
    }

    return {
        tick(audioTime, dt, gameSpeed) {
            spawnDue(audioTime);
            moveAndCullActive(dt, gameSpeed);
        },
        getActive() {
            return active;
        },
        dispose() {
            for (const o of active) {
                gameLayer.removeChild(o);
                o.destroy({ children: true });
            }
            active.length = 0;
            cursor = 0;
        },
    };
}
