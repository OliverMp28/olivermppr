// Sistema de modales slide-in (PS5-style). <dialog> nativo + Tailwind/CSS.
//
// Doc 06 §5 + §10: paneles deslizantes desde la derecha, no páginas nuevas.
// El shader del fondo nunca se oculta — solo se desatura un poco vía backdrop.
//
// API:
//   - createModal(id, { title, body, footer })  → instancia un <dialog> y lo
//     monta en #modal-root. Idempotente: si ya existe el id, devuelve el viejo.
//   - openProfileModal()      — hidrata desde client.getUser(). Si null, CTA login.
//   - openLevelsModal()       — placeholder Bloque 7.
//   - openRankingModal()      — placeholder Bloque 7.
//   - openSettingsModal()     — funcional con localStorage (Lote D).
//   - closeAllModals()        — cierra cualquier <dialog> abierto.
//
// Lote B implementa solo createModal + openProfileModal. Los demás openers
// existen como stubs que ya pueden ser llamados (Lote D los completa).

import { getUser, logout, isAuthenticated } from '../api/client.js';
import { isBridgeEmbedded } from '../iframe/bridge.js';
import { renderAvatar } from './avatar.js';
import {
    getVolume, setVolume,
    getReduceEffects, setReduceEffects,
    listKeys, clearAll,
} from './settings.js';

const MODAL_ROOT_ID = 'modal-root';

/**
 * @param {string} id
 * @param {{ title: string, body: HTMLElement | string, footer?: HTMLElement }} opts
 * @returns {HTMLDialogElement}
 */
export function createModal(id, opts) {
    const root = document.getElementById(MODAL_ROOT_ID);
    if (root === null) {
        throw new Error(`createModal: #${MODAL_ROOT_ID} no existe en el DOM`);
    }

    const existing = document.getElementById(`modal-${id}`);
    if (existing instanceof HTMLDialogElement) {
        // Reusar: actualizar body/footer si los pasaron de nuevo.
        rebuildModalContent(existing, opts);
        return existing;
    }

    const dialog = document.createElement('dialog');
    dialog.id = `modal-${id}`;
    dialog.className = 'daino-modal';
    dialog.setAttribute('aria-labelledby', `modal-${id}-title`);

    rebuildModalContent(dialog, opts);
    root.appendChild(dialog);
    return dialog;
}

function rebuildModalContent(dialog, opts) {
    const { title, body, footer } = opts;
    dialog.innerHTML = '';

    const shell = document.createElement('div');
    shell.className = 'modal-shell';

    const header = document.createElement('header');
    header.className = 'modal-header';
    const h2 = document.createElement('h2');
    h2.id = `${dialog.id}-title`;
    h2.textContent = title;
    const closeBtn = document.createElement('button');
    closeBtn.className = 'modal-close';
    closeBtn.setAttribute('type', 'button');
    closeBtn.setAttribute('aria-label', 'Cerrar');
    closeBtn.dataset.interactive = 'true';
    closeBtn.textContent = '×';
    closeBtn.addEventListener('click', () => dialog.close());
    header.appendChild(h2);
    header.appendChild(closeBtn);
    shell.appendChild(header);

    const bodyEl = document.createElement('section');
    bodyEl.className = 'modal-body';
    if (typeof body === 'string') {
        bodyEl.textContent = body;
    } else if (body instanceof HTMLElement) {
        bodyEl.appendChild(body);
    }
    shell.appendChild(bodyEl);

    if (footer) {
        const footerEl = document.createElement('footer');
        footerEl.className = 'modal-footer';
        footerEl.appendChild(footer);
        shell.appendChild(footerEl);
    }

    dialog.appendChild(shell);

    // Click en backdrop cierra el modal. <dialog>::backdrop no es clicable
    // directamente, pero el click en el dialog cuando el target es el dialog
    // mismo (no un hijo) implica click sobre el área negra.
    dialog.addEventListener('click', (ev) => {
        if (ev.target === dialog) dialog.close();
    });
}

/** Cierra cualquier <dialog> abierto dentro de #modal-root. */
export function closeAllModals() {
    const root = document.getElementById(MODAL_ROOT_ID);
    if (root === null) return;
    for (const d of root.querySelectorAll('dialog[open]')) {
        if (d instanceof HTMLDialogElement) d.close();
    }
}

// --------------------------- Modal: PERFIL ---------------------------

export function openProfileModal() {
    const user = getUser();
    const body = document.createElement('div');
    body.className = 'flex flex-col gap-4';

    if (user !== null) {
        // Usuario logueado — mostrar info + LOGOUT.
        const avatarRow = document.createElement('div');
        avatarRow.className = 'flex items-center gap-3';

        const avatar = renderAvatar(user.avatar_url, user.username, 56, { tag: 'div' });
        avatarRow.appendChild(avatar);

        const meta = document.createElement('div');
        meta.className = 'flex flex-col';
        const username = document.createElement('strong');
        username.textContent = user.username;
        username.style.fontSize = '1.1rem';
        const voutId = document.createElement('span');
        voutId.textContent = `vout_id: ${user.vout_id}`;
        voutId.style.fontSize = '0.75rem';
        voutId.style.opacity = '0.6';
        voutId.style.fontFamily = 'monospace';
        meta.appendChild(username);
        meta.appendChild(voutId);
        avatarRow.appendChild(meta);

        body.appendChild(avatarRow);

        const note = document.createElement('p');
        note.style.fontSize = '0.9rem';
        note.style.opacity = '0.75';
        note.textContent = 'Has iniciado sesión con Vout. Tu progreso global se guardará cuando se cablée el ranking (Bloque 7).';
        body.appendChild(note);
    } else {
        // Anónimo — CTA login.
        const note = document.createElement('p');
        note.textContent = 'No has iniciado sesión. Daino es jugable sin cuenta, pero el ranking global y los niveles compartidos requieren login con Vout.';
        body.appendChild(note);
    }

    const footer = document.createElement('div');
    footer.style.display = 'flex';
    footer.style.gap = '0.5rem';
    footer.style.width = '100%';
    footer.style.justifyContent = 'flex-end';

    if (user !== null) {
        if (isBridgeEmbedded()) {
            // En iframe el LOGOUT no aplica — la sesión la maneja Vout.
            // Botón "Volver al portal" cierra el iframe via postMessage.
            const exitBtn = button('Volver al portal', 'daino-btn');
            exitBtn.addEventListener('click', () => {
                window.parent?.postMessage({ type: 'EXIT' }, '*');  // OJO: parent valida origin de su lado
            });
            footer.appendChild(exitBtn);
        } else {
            const logoutBtn = button('Cerrar sesión', 'daino-btn daino-btn--danger');
            logoutBtn.addEventListener('click', async () => {
                logoutBtn.disabled = true;
                logoutBtn.textContent = 'Cerrando…';
                await logout();
                // El handler de daino:auth:logout en auth.js repintará el chip.
                // Cerramos el modal y rehacemos contenido para reflejar estado.
                const modal = document.getElementById('modal-profile');
                if (modal instanceof HTMLDialogElement) modal.close();
            });
            footer.appendChild(logoutBtn);
        }
    } else {
        const loginLink = document.createElement('a');
        loginLink.href = '/auth/login';
        loginLink.className = 'daino-btn daino-btn--primary';
        loginLink.dataset.interactive = 'true';
        loginLink.textContent = 'Iniciar sesión con Vout';
        footer.appendChild(loginLink);
    }

    const dialog = createModal('profile', {
        title: isAuthenticated() ? 'Tu perfil' : 'Iniciar sesión',
        body,
        footer,
    });
    dialog.showModal();
}

// --------------------------- Modal: NIVELES (placeholder Bloque 7) ---------------------------

export function openLevelsModal() {
    const body = document.createElement('div');
    body.className = 'daino-modal__placeholder';

    const p1 = document.createElement('p');
    p1.textContent = 'El catálogo de niveles públicos aparecerá aquí cuando se cablée el Bloque 7.';
    const p2 = document.createElement('p');
    p2.style.opacity = '0.75';
    p2.textContent = 'Mientras: arrastra cualquier MP3 sobre la pantalla — Daino genera el nivel localmente sin subir nada al servidor.';
    body.appendChild(p1);
    body.appendChild(p2);

    const dialog = createModal('levels', { title: 'Niveles', body });
    dialog.showModal();
}

// --------------------------- Modal: RANKING (placeholder Bloque 7) ---------------------------

export function openRankingModal() {
    const body = document.createElement('div');
    body.className = 'daino-modal__placeholder';

    const p1 = document.createElement('p');
    p1.textContent = 'El ranking global aparecerá aquí cuando se cablée el Bloque 7.';
    body.appendChild(p1);

    if (!isAuthenticated()) {
        const cta = document.createElement('p');
        cta.style.opacity = '0.75';
        cta.textContent = 'Inicia sesión con Vout para que tus partidas cuenten en el ranking global.';
        body.appendChild(cta);

        const footer = document.createElement('div');
        footer.style.display = 'flex';
        footer.style.justifyContent = 'flex-end';
        const link = document.createElement('a');
        link.href = '/auth/login';
        link.className = 'daino-btn daino-btn--primary';
        link.dataset.interactive = 'true';
        link.textContent = 'Iniciar sesión';
        footer.appendChild(link);

        const dialog = createModal('ranking', { title: 'Ranking', body, footer });
        dialog.showModal();
        return;
    }

    const dialog = createModal('ranking', { title: 'Ranking', body });
    dialog.showModal();
}

// --------------------------- Modal: PAUSA ---------------------------

/**
 * Abre el modal de pausa. Centrado sobre el viewport (no slide-in lateral
 * — la pausa es un break "fuerte", no un panel secundario).
 *
 * @param {{ onResume: () => void, onExit: () => void }} cfg
 */
export function openPauseModal(cfg) {
    let exited = false;

    const body = document.createElement('div');
    body.className = 'daino-pause__body';
    const note = document.createElement('p');
    note.textContent = 'La canción está suspendida. Reanuda para continuar exactamente donde lo dejaste.';
    note.style.opacity = '0.85';
    body.appendChild(note);

    const footer = document.createElement('div');
    footer.style.display = 'flex';
    footer.style.gap = '0.5rem';
    footer.style.justifyContent = 'flex-end';
    footer.style.width = '100%';

    const exitBtn = button('Salir al menú', 'daino-btn daino-btn--danger');
    exitBtn.addEventListener('click', () => {
        exited = true;
        dialog.close();
        cfg.onExit();
    });
    const resumeBtn = button('Reanudar', 'daino-btn daino-btn--primary');
    resumeBtn.addEventListener('click', () => dialog.close());

    footer.appendChild(exitBtn);
    footer.appendChild(resumeBtn);

    const dialog = createModal('pause', { title: 'Pausa', body, footer });
    // El `daino-modal--center` cambia el layout: en lugar de slide-in lateral,
    // queda centrado sobre el viewport. Doc 06 §4.2 lo pinta así.
    dialog.classList.add('daino-modal--center');

    // Cualquier vía de cierre (botón ×, click backdrop, ESC nativo, click
    // Reanudar) que NO haya pasado por "Salir al menú" → equivale a Resume.
    dialog.addEventListener('close', () => {
        if (!exited) cfg.onResume();
    }, { once: true });

    dialog.showModal();
    // Foco inicial al botón Reanudar — Enter accept-cierra.
    queueMicrotask(() => resumeBtn.focus());
}

// --------------------------- Modal: AJUSTES (funcional con localStorage) ---------------------------

/**
 * @param {{ getActiveAudioEngine?: () => any | null }} [opts]
 *   getActiveAudioEngine: callback para obtener el AudioEngine activo y
 *   aplicar volumen en vivo. Lo inyecta el menú al wirear (evita import
 *   circular UI ↔ AppController).
 */
export function openSettingsModal(opts = {}) {
    const getEngine = opts.getActiveAudioEngine ?? (() => null);

    const body = document.createElement('div');
    body.className = 'daino-settings';

    // ----- Volumen master -----
    const volRow = document.createElement('section');
    volRow.className = 'daino-settings__row';
    const volLabel = document.createElement('label');
    volLabel.className = 'daino-settings__label';
    volLabel.textContent = 'Volumen';
    const volValue = document.createElement('span');
    volValue.className = 'daino-settings__value';
    const initialVol = getVolume();
    volValue.textContent = `${Math.round(initialVol * 100)}%`;
    const volSlider = document.createElement('input');
    volSlider.type = 'range';
    volSlider.min = '0';
    volSlider.max = '1';
    volSlider.step = '0.01';
    volSlider.value = String(initialVol);
    volSlider.dataset.interactive = 'true';
    volSlider.setAttribute('aria-label', 'Volumen master');
    volSlider.addEventListener('input', () => {
        const v = Number.parseFloat(volSlider.value);
        setVolume(v, getEngine());
        volValue.textContent = `${Math.round(v * 100)}%`;
    });
    volLabel.setAttribute('for', 'daino-settings-vol');
    volSlider.id = 'daino-settings-vol';
    volRow.appendChild(volLabel);
    volRow.appendChild(volSlider);
    volRow.appendChild(volValue);
    body.appendChild(volRow);

    // ----- Reducir efectos visuales (placeholder funcional para Bloque 8) -----
    const fxRow = document.createElement('section');
    fxRow.className = 'daino-settings__row';
    const fxLabel = document.createElement('label');
    fxLabel.className = 'daino-settings__label';
    fxLabel.htmlFor = 'daino-settings-fx';
    fxLabel.textContent = 'Reducir efectos visuales';
    const fxToggle = document.createElement('input');
    fxToggle.type = 'checkbox';
    fxToggle.id = 'daino-settings-fx';
    fxToggle.dataset.interactive = 'true';
    fxToggle.checked = getReduceEffects();
    fxToggle.addEventListener('change', () => {
        setReduceEffects(fxToggle.checked);
    });
    const fxNote = document.createElement('p');
    fxNote.className = 'daino-settings__hint';
    fxNote.textContent = 'Se guardará para que partículas y shader extras se desactiven cuando Bloque 8 lo cablée.';
    fxRow.appendChild(fxLabel);
    fxRow.appendChild(fxToggle);
    fxRow.appendChild(fxNote);
    body.appendChild(fxRow);

    // ----- Borrar datos locales -----
    const clearRow = document.createElement('section');
    clearRow.className = 'daino-settings__row';
    const clearLabel = document.createElement('div');
    clearLabel.className = 'daino-settings__label';
    clearLabel.textContent = 'Borrar datos locales';
    const clearNote = document.createElement('p');
    clearNote.className = 'daino-settings__hint';
    const keys = listKeys();
    clearNote.textContent = keys.length === 0
        ? 'Aún no hay datos locales que borrar.'
        : `Borrará ${keys.length} entradas de localStorage (${keys.join(', ')}).`;
    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'daino-btn daino-btn--danger';
    clearBtn.dataset.interactive = 'true';
    clearBtn.textContent = 'Borrar';
    clearBtn.disabled = keys.length === 0;
    clearBtn.addEventListener('click', () => {
        const sure = window.confirm('¿Borrar todos los datos locales de Daino? Esto incluye volumen y preferencias.');
        if (!sure) return;
        const removed = clearAll();
        clearNote.textContent = `Borradas ${removed} entradas. Recarga para aplicar valores por defecto.`;
        clearBtn.disabled = true;
    });
    clearRow.appendChild(clearLabel);
    clearRow.appendChild(clearNote);
    clearRow.appendChild(clearBtn);
    body.appendChild(clearRow);

    const dialog = createModal('settings', { title: 'Ajustes', body });
    dialog.showModal();
}

// --------------------------- Helpers internos ---------------------------

function placeholderBody(text) {
    const p = document.createElement('p');
    p.style.opacity = '0.85';
    p.textContent = text;
    return p;
}

function button(label, className) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = className;
    b.dataset.interactive = 'true';
    b.textContent = label;
    return b;
}

if (import.meta.hot) {
    import.meta.hot.accept(() => {
        // Al recargar este módulo, cerramos modales abiertos para evitar
        // estado huérfano entre versiones.
        closeAllModals();
    });
}
