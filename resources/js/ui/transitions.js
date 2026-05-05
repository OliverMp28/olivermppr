// Transiciones UI compartidas. CSS-driven, JS solo añade/quita un overlay.
//
// `fadeOverlay({ onMidpoint })` cubre el caso base del doc 06 §4.3: fade a
// negro 300ms al entrar/salir de partida, mientras el black está totalmente
// opaco se hace el cambio (mount HUD, start GameSession, etc.) que el user
// no debería ver.
//
// `prefers-reduced-motion` corta la duración a 0 — el usuario sigue viendo
// el cambio pero sin la animación.

const OVERLAY_ID = 'daino-fade-overlay';

const REDUCED_MOTION = matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * @param {{ totalMs?: number, onMidpoint?: () => void | Promise<void> }} opts
 * @returns {Promise<void>}  Resuelve cuando el overlay se quitó del DOM.
 */
export function fadeOverlay(opts = {}) {
    const totalMs = REDUCED_MOTION ? 0 : (opts.totalMs ?? 600);
    const halfMs = Math.floor(totalMs / 2);
    const onMidpoint = opts.onMidpoint ?? (() => {});

    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.id = OVERLAY_ID;
        overlay.style.position = 'fixed';
        overlay.style.inset = '0';
        overlay.style.background = '#000';
        overlay.style.zIndex = '70';     // sobre modales (50) — cubre todo
        overlay.style.opacity = '0';
        overlay.style.pointerEvents = 'none';
        overlay.style.transition = `opacity ${halfMs}ms ease-in-out`;
        document.body.appendChild(overlay);

        // Forzar reflow para que la transición de 0 → 1 se anime y no se
        // optimice como salto instantáneo. Leer offsetWidth basta.
        void overlay.offsetWidth;
        overlay.style.opacity = '1';

        const afterFadeIn = async () => {
            try {
                await Promise.resolve(onMidpoint());
            } catch (err) {
                console.error('[transitions] onMidpoint falló:', err);
                // Aún si falla seguimos con el fade-out — no queremos
                // dejar la pantalla en negro.
            }
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.remove();
                resolve();
            }, halfMs + 20);  // +20ms de margen para que la transición complete
        };

        if (halfMs === 0) {
            // Reduced motion — saltarse las dos esperas.
            void afterFadeIn();
        } else {
            setTimeout(() => { void afterFadeIn(); }, halfMs + 20);
        }
    });
}
