// Obstáculo pasivo (cactus) que se desplaza de derecha a izquierda. Lo
// instancia el Spawner desde la timeline pre-computada y lo destruye cuando
// sale por la izquierda. No conoce ni al Dino ni al audio: solo expone su
// hitbox AABB para que physics.js resuelva la colisión. Hay 2 kinds:
// CACTUS_SMALL (alto/angosto) y CACTUS_WIDE (bajo/ancho), definidos en
// config.js — el kind decide tamaño y color del placeholder visual.

import { Container, Graphics } from 'pixi.js';
import { OBSTACLE_KIND, OBSTACLE_HITBOX_PADDING } from '../config.js';

export class Obstacle extends Container {
    /**
     * @param {'CACTUS_SMALL' | 'CACTUS_WIDE'} kind  Clave de OBSTACLE_KIND.
     * @param {number} spawnX   X inicial en stage coords (típicamente fuera del frame por la derecha).
     * @param {number} groundY  Y del suelo. La base del obstáculo se alinea ahí.
     */
    constructor(kind, spawnX, groundY) {
        super();

        const data = OBSTACLE_KIND[kind];

        this.kind = kind;
        this._w = data.w;
        this._h = data.h;

        this.x = spawnX;
        this.y = groundY - data.h;

        // Placeholder visual del Bloque 5. Pívot en (0,0) — coherente con Dino,
        // así getHitbox() puede sumar padding directo a (this.x, this.y) sin
        // compensar transform.
        const sprite = new Graphics()
            .rect(0, 0, data.w, data.h)
            .fill(data.color);
        this.addChild(sprite);
    }

    /**
     * Avanza la posición X hacia la izquierda. Pasivo: no integra física.
     * @param {number} dt         Delta time en segundos.
     * @param {number} gameSpeed  Velocidad de scroll en px/s (positivo).
     */
    update(dt, gameSpeed) {
        this.x -= gameSpeed * dt;
    }

    /**
     * Rectángulo de colisión en stage coords con padding aplicado — el cactus
     * visual tiene "puntas" que no deberían contar como cuerpo, así que el
     * hitbox queda un pelín dentro del sprite.
     * @returns {{ x: number, y: number, w: number, h: number }}
     */
    getHitbox() {
        const p = OBSTACLE_HITBOX_PADDING;
        return {
            x: this.x + p.left,
            y: this.y + p.top,
            w: this._w - p.left - p.right,
            h: this._h - p.top - p.bottom,
        };
    }

    /** True cuando el obstáculo ya no es visible. Margen de 100px para que el destroy no se note. */
    isOffscreenLeft() {
        return this.x + this._w < -100;
    }
}
