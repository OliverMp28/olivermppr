// Drag & drop de MP3 sobre todo el viewport. Bloque 4.
//
// Flujo:
//   1. dragover sobre body — anota `data-drag="active"` (CSS dibuja el borde
//      pulsante). Hay que llamar preventDefault() en dragover y drop o el
//      navegador abre el archivo en su lugar.
//   2. drop — lee el primer File del DataTransfer, valida MIME y tamaño,
//      lee como ArrayBuffer, decodifica con AudioEngine, atacha al engine
//      y lanza play().
//   3. Oculta #hint con .is-hidden tras el primer audio:ready exitoso.
//
// Validaciones (defensa frontend; el backend revalida en Bloque 7 al subir):
//   - MIME: audio/mpeg. Algunos OS reportan vacío; aceptamos también si el
//     nombre termina en .mp3.
//   - Tamaño: contra <meta name="daino:upload-max-mb">. Default 10 MB.
//
// Errores: toast in-DOM autodescartable a los 4s. role="alert" para a11y.

import { AudioEngine } from '@/game/audio/AudioEngine.js';

const META_UPLOAD_MAX = 'daino:upload-max-mb';

let audioEngine = null;
let mountedEngine = null;

function readUploadMaxBytes() {
    const meta = document.querySelector(`meta[name="${META_UPLOAD_MAX}"]`);
    const raw = meta?.getAttribute('content');
    const mb = Number.parseInt(raw ?? '10', 10);
    const safe = Number.isFinite(mb) && mb > 0 ? mb : 10;
    return safe * 1024 * 1024;
}

function isMp3(file) {
    if (file.type === 'audio/mpeg' || file.type === 'audio/mp3') return true;
    return /\.mp3$/i.test(file.name);
}

function showToast(message, severity = 'error') {
    const el = document.createElement('div');
    el.className = 'daino-toast';
    el.dataset.severity = severity;
    el.setAttribute('role', severity === 'error' ? 'alert' : 'status');
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => { el.remove(); }, 4000);
}

function hideHint() {
    const hint = document.getElementById('hint');
    if (hint) hint.classList.add('is-hidden');
}

function showHint() {
    const hint = document.getElementById('hint');
    if (hint) hint.classList.remove('is-hidden');
}

async function handleFile(file) {
    const maxBytes = readUploadMaxBytes();
    if (!isMp3(file)) {
        showToast('Solo MP3 por ahora — el archivo no parece audio/mpeg.');
        return;
    }
    if (file.size > maxBytes) {
        const mb = (maxBytes / (1024 * 1024)) | 0;
        showToast(`Archivo demasiado grande — máximo ${mb} MB.`);
        return;
    }

    if (audioEngine === null) {
        audioEngine = new AudioEngine();
    }

    try {
        await audioEngine.start();
        const arrayBuffer = await file.arrayBuffer();
        const audioBuffer = await audioEngine.decode(arrayBuffer);

        // Conectar al engine si no estaba ya.
        if (mountedEngine && mountedEngine.attachAudio) {
            mountedEngine.attachAudio(audioEngine);
        }

        audioEngine.play(audioBuffer);
        hideHint();

        window.dispatchEvent(new CustomEvent('audio:ready', {
            detail: { duration: audioBuffer.duration, name: file.name },
        }));
    } catch (err) {
        console.error('[upload] decode/play failed:', err);
        showToast('No pude decodificar el MP3. ¿Está corrupto?');
        showHint();
    }
}

function onDragOver(ev) {
    ev.preventDefault();
    document.body.dataset.drag = 'active';
    if (ev.dataTransfer) ev.dataTransfer.dropEffect = 'copy';
}

function onDragLeave(ev) {
    // dragleave se dispara también al pasar entre hijos. Solo limpiamos si el
    // puntero salió de la ventana (relatedTarget null suele indicarlo).
    if (ev.relatedTarget === null || ev.relatedTarget === undefined) {
        delete document.body.dataset.drag;
    }
}

function onDrop(ev) {
    ev.preventDefault();
    delete document.body.dataset.drag;

    const file = ev.dataTransfer?.files?.[0];
    if (!file) return;
    void handleFile(file);
}

/**
 * @param {{ engine: { attachAudio: (e: AudioEngine) => void } }} opts
 */
export function setupUpload(opts) {
    mountedEngine = opts?.engine ?? null;
    document.body.addEventListener('dragover', onDragOver);
    document.body.addEventListener('dragleave', onDragLeave);
    document.body.addEventListener('drop', onDrop);
}
