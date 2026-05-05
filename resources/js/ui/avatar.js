// Helpers compartidos para renderizar avatares de usuarios.
//
// Se separa porque el chip (auth.js) y el modal de perfil (modals.js) ambos
// pintan avatares con la misma forma — antes vivían duplicados. Cualquier
// otro punto de UI que muestre un user (rankings, comments, niveles públicos
// del Bloque 7+) reusará estos helpers.
//
// Por qué `resolveAvatarUrl`: el `avatar_url` que devuelve Vout es un path
// relativo a SU dominio (`/storage/avatars/X.png`). Si lo metemos crudo en
// `<img src>` el navegador lo resuelve contra el origin de Daino, dando 404.
// Aquí prefijeamos con `<meta name="daino:vout-origin">` cuando detectamos
// que el path es relativo.

const META_VOUT_ORIGIN = 'daino:vout-origin';

let cachedVoutOrigin = undefined;

function readVoutOrigin() {
    if (cachedVoutOrigin !== undefined) return cachedVoutOrigin;
    const meta = document.querySelector(`meta[name="${META_VOUT_ORIGIN}"]`);
    const value = meta?.getAttribute('content');
    if (typeof value === 'string' && value !== '' && value !== '*') {
        try {
            cachedVoutOrigin = new URL(value).origin;
        } catch {
            cachedVoutOrigin = null;
        }
    } else {
        cachedVoutOrigin = null;
    }
    return cachedVoutOrigin;
}

/**
 * Convierte un `avatar_url` recibido de la API en una URL cargable.
 *  - null/undefined → null
 *  - http(s)://… absoluta → tal cual
 *  - /path relativo → prefijado con el origin de Vout (si está configurado)
 *  - Cualquier otra cosa (data URL, blob, etc.) → tal cual
 *
 * @param {string | null | undefined} url
 * @returns {string | null}
 */
export function resolveAvatarUrl(url) {
    if (url === null || url === undefined || url === '') return null;
    if (/^https?:\/\//i.test(url)) return url;
    if (url.startsWith('/')) {
        const origin = readVoutOrigin();
        if (origin === null) return null;   // sin origin no podemos resolver
        return origin + url;
    }
    return url;
}

/**
 * Render de avatar circular con fallback a la inicial del username si no hay
 * URL o falla el load. El elemento devuelto es ya un `<span>` o `<div>` listo
 * para añadir al DOM — el caller decide el contenedor padre.
 *
 * @param {string | null | undefined} rawUrl
 * @param {string} username
 * @param {number} size  Tamaño en píxeles. Cuadrado.
 * @param {{ tag?: 'span' | 'div' }} [opts]
 * @returns {HTMLElement}
 */
export function renderAvatar(rawUrl, username, size, opts = {}) {
    const tag = opts.tag === 'div' ? 'div' : 'span';
    const wrap = document.createElement(tag);
    wrap.style.display = 'inline-flex';
    wrap.style.width = `${size}px`;
    wrap.style.height = `${size}px`;
    wrap.style.borderRadius = '50%';
    wrap.style.overflow = 'hidden';
    wrap.style.flex = 'none';
    wrap.style.alignItems = 'center';
    wrap.style.justifyContent = 'center';
    wrap.style.background = 'color-mix(in oklch, var(--color-daino-beat) 80%, transparent)';
    wrap.style.color = '#fff';
    wrap.style.fontWeight = '600';
    wrap.style.fontSize = `${Math.round(size * 0.42)}px`;

    const initial = (username && username[0]) ? username[0].toUpperCase() : '?';
    wrap.textContent = initial;

    const url = resolveAvatarUrl(rawUrl);
    if (url !== null) {
        const img = document.createElement('img');
        img.src = url;
        img.alt = '';
        img.width = size;
        img.height = size;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.addEventListener('load', () => {
            wrap.textContent = '';
            wrap.appendChild(img);
        });
        // En 'error' dejamos el fallback (ya está pintado) — no hacemos nada
        // explícito. Esto cubre tanto 404 como CORS o assets bloqueados.
    }
    return wrap;
}
