// input.js — fuente única de input del Bloque 5. Tres entradas:
//
//   1. Teclado: Space / ArrowUp → emite 'jump'.
//   2. Touch: cualquier tap en document.body → emite 'jump'.
//      Solo salto en este bloque, así que no partimos la pantalla por mitad.
//   3. GAME_ACTION del bridge postMessage (modo iframe en Vout):
//      `{ type:'GAME_ACTION', payload:'JUMP' }` → emite 'jump'.
//
// Diseño:
//   - Singleton que la GameSession instancia en `start()` y dispone en `stop()`.
//   - EventEmitter mínimo: `on(event, fn)`, `off(event, fn)`. Sin máquina de
//     estados — solo un canal 'jump' por ahora. Si Bloque 6 añade 'duck' o
//     'pause', se añaden canales nuevos sin romper este.
//   - Desacoplado de Pixi y de la entidad — la GameSession suscribe.
//   - El bridge se importa lazy (dentro del setup) para no forzar el load
//     del módulo iframe en standalone.

import { setOnGameAction, isBridgeEmbedded } from '../../iframe/bridge.js';

const KEYS_JUMP = new Set(['Space', 'ArrowUp']);

class InputHub {
    constructor() {
        /** @type {Map<string, Set<Function>>} */
        this._handlers = new Map();
        this._keyDown = null;
        this._touchStart = null;
        this._mounted = false;
    }

    on(event, fn) {
        let set = this._handlers.get(event);
        if (!set) {
            set = new Set();
            this._handlers.set(event, set);
        }
        set.add(fn);
    }

    off(event, fn) {
        const set = this._handlers.get(event);
        if (set) set.delete(fn);
    }

    emit(event, payload) {
        const set = this._handlers.get(event);
        if (!set) return;
        for (const fn of set) fn(payload);
    }

    /**
     * Cabea los listeners DOM y el handler del bridge. Idempotente — segunda
     * llamada es no-op para evitar duplicar listeners si el caller despista.
     */
    setup() {
        if (this._mounted) return;
        this._mounted = true;

        this._keyDown = (ev) => {
            if (KEYS_JUMP.has(ev.code)) {
                // preventDefault evita que Space haga scroll en algunas
                // configuraciones del viewport (overflow visible, etc.).
                ev.preventDefault();
                this.emit('jump');
            }
        };
        window.addEventListener('keydown', this._keyDown);

        this._touchStart = (ev) => {
            // Si el tap viene encima de un toast/overlay (role=alert), lo
            // dejamos burbujear sin emitir 'jump' — sino el user no puede
            // descartarlos visualmente.
            if (ev.target instanceof HTMLElement && ev.target.closest('[role="alert"]')) {
                return;
            }
            ev.preventDefault();
            this.emit('jump');
        };
        // passive:false porque hacemos preventDefault. En móviles el navegador
        // queja si lo dejas passive y luego cancelas el evento.
        document.body.addEventListener('touchstart', this._touchStart, { passive: false });

        // Bridge: solo si estamos embebidos (Vout). En standalone el setter
        // no hace daño pero tampoco llega nada.
        if (isBridgeEmbedded()) {
            setOnGameAction((payload) => {
                if (payload === 'JUMP') this.emit('jump');
            });
        }
    }

    /** Despide listeners. La GameSession llama esto en `stop()`. */
    dispose() {
        if (!this._mounted) return;
        if (this._keyDown) window.removeEventListener('keydown', this._keyDown);
        if (this._touchStart) document.body.removeEventListener('touchstart', this._touchStart);
        if (isBridgeEmbedded()) setOnGameAction(null);
        this._handlers.clear();
        this._keyDown = null;
        this._touchStart = null;
        this._mounted = false;
    }
}

/** Instancia compartida. Daino es single-page, una sola sesión activa a la vez. */
export const Input = new InputHub();
