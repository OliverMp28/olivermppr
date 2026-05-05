// Capa 2 del z-stack visual (doc 06 §3): partículas ambientales que reaccionan
// al volumen del audio (motas, destellos). En Bloque 4 es solo un stub
// estructural — el ParticleContainer existe pero no contiene partículas
// todavía. Bloque 5 lo afinará: spawneo según uRMS, partículas con `Particle`
// (NO Sprite — doc 09 §2.2 lo prohíbe explícitamente para ParticleContainer).
//
// Decisiones que YA fija este stub:
//   - `boundsArea` OBLIGATORIO o el cull falla silenciosamente (doc 09 §2.2).
//   - `dynamicProperties: { position: true }` — solo position cambia frame a
//     frame; scale/rotation/color se uploaden una vez al GPU (gain de v8).
//   - Bloque 5 puede mutar `particleChildren` directamente (más rápido que
//     pasar por addParticle/removeParticle en hot loops).

import { ParticleContainer, Rectangle } from 'pixi.js';

/**
 * Crea el contenedor de partículas. Sin partículas todavía — el caller (engine)
 * lo agrega al stage; Bloque 5 lo poblará en respuesta al AudioEngine.
 *
 * @param {{ width: number, height: number }} viewport
 */
export function createParticleLayer(viewport) {
    const layer = new ParticleContainer({
        boundsArea: new Rectangle(0, 0, viewport.width, viewport.height),
        dynamicProperties: {
            position: true,
            scale: false,
            rotation: false,
            color: false,
        },
    });
    layer.label = 'particles';
    return layer;
}
