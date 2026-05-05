// Entidad protagonista. Solo SALTA — no se agacha, no cambia de carril.
// Su X es fija (`DINO_X`); la Y la mueve `physics.js` cada frame integrando
// gravedad y clampeando al suelo. Aquí solo vive el estado (vy, grounded),
// el visual placeholder y la acción `jump()`. Mantener separación
// entidad/sistema: la integración NO ocurre aquí.

import { Container, Graphics } from 'pixi.js';
import { DINO_X, DINO_SIZE, DINO_HITBOX_PADDING, JUMP_VELOCITY } from '../config.js';

export class Dino extends Container {
    constructor() {
        super();

        this.x = DINO_X;
        this.y = 0;

        this.vy = 0;
        this.grounded = true;

        // Placeholder visual del Bloque 5. El pívot queda en (0,0) para que
        // (this.x, this.y) sea la esquina superior-izquierda — así physics.js
        // puede hacer `dino.y = groundY - DINO_SIZE.h` sin compensar pívot.
        const sprite = new Graphics()
            .rect(0, 0, DINO_SIZE.w, DINO_SIZE.h)
            .fill(0xffffff);
        this.addChild(sprite);
    }

    /** Aplica el impulso de salto solo si está en el suelo. Sin double-jump. */
    jump() {
        if (!this.grounded) return;
        this.vy = -JUMP_VELOCITY;
        this.grounded = false;
    }

    /**
     * Rectángulo de colisión en coordenadas del stage, con padding aplicado
     * para que el hitbox sea más perdonador que la silueta visual.
     * @returns {{ x: number, y: number, w: number, h: number }}
     */
    getHitbox() {
        const p = DINO_HITBOX_PADDING;
        return {
            x: this.x + p.left,
            y: this.y + p.top,
            w: DINO_SIZE.w - p.left - p.right,
            h: DINO_SIZE.h - p.top - p.bottom,
        };
    }
}
