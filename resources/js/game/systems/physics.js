// physics.js — integración Euler + AABB. Stateless: una función pura por
// cada cosa. La GameSession llama `step()` cada frame con el Dino y la lista
// de obstáculos activos.
//
// Por qué separado del Dino: la entidad solo expone estado (vy, grounded) y
// acciones (jump). El "cómo" se mueve y "cómo" colisiona vive aquí, lo que
// facilita testear el sistema en aislamiento (no necesitas instanciar Pixi
// para validar la matemática).

import {
    GRAVITY,
    GROUND_OFFSET_PX,
    DINO_SIZE,
} from '../config.js';

/**
 * Calcula `groundY` en stage coords, dado la altura del viewport.
 * groundY es la coordenada Y del SUELO (no de la base del Dino) — es donde
 * los obstáculos se asientan también.
 */
export function computeGroundY(viewportHeight) {
    return viewportHeight - GROUND_OFFSET_PX;
}

/**
 * Integra una unidad de tiempo sobre el Dino: aplica gravedad, mueve, clampa
 * al suelo, recompone el flag `grounded`. Mutación in-place del Dino —
 * ahorramos GC pressure en el frame loop.
 *
 * @param {{ y: number, vy: number, grounded: boolean }} dino
 * @param {number} dt        Delta time en segundos.
 * @param {number} groundY   Coordenada Y del suelo (= computeGroundY(viewportHeight)).
 */
export function integrateDino(dino, dt, groundY) {
    // El "y" del Dino es la esquina superior. La base del Dino está en
    // `dino.y + DINO_SIZE.h`. Cuando esa base toca groundY, el Dino aterriza.
    dino.vy += GRAVITY * dt;
    dino.y += dino.vy * dt;

    const baseY = dino.y + DINO_SIZE.h;
    if (baseY >= groundY) {
        dino.y = groundY - DINO_SIZE.h;
        dino.vy = 0;
        dino.grounded = true;
    } else {
        dino.grounded = false;
    }
}

/**
 * AABB collision test entre dos hitboxes. Hitbox = { x, y, w, h } en stage coords.
 * @returns {boolean}
 */
export function aabbHit(a, b) {
    return (
        a.x < b.x + b.w &&
        a.x + a.w > b.x &&
        a.y < b.y + b.h &&
        a.y + a.h > b.y
    );
}

/**
 * Devuelve el primer obstáculo activo que choca con el Dino, o null.
 * No se sale del primer hit — single-hit = game over (decisión del Bloque 5).
 *
 * @param {{ getHitbox: () => any }} dino
 * @param {Array<{ getHitbox: () => any }>} obstacles
 * @returns {object | null}
 */
export function findCollision(dino, obstacles) {
    const dh = dino.getHitbox();
    for (let i = 0; i < obstacles.length; i++) {
        const oh = obstacles[i].getHitbox();
        // Early-exit horizontal: si el obstáculo ya está totalmente a la
        // izquierda o totalmente a la derecha del Dino, no puede chocar.
        if (oh.x + oh.w < dh.x) continue;
        if (oh.x > dh.x + dh.w) continue;
        if (aabbHit(dh, oh)) return obstacles[i];
    }
    return null;
}
