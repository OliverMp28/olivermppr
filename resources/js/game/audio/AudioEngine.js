// AudioEngine — wrapper sobre Web Audio API.
//
// Diseño:
//   - Lazy: el AudioContext NO se crea en el constructor. Lo crea start() en
//     respuesta a un gesto del user (drop del MP3) — autoplay policy del
//     navegador exige gesto previo o el contexto arranca `suspended`.
//   - Una instancia maneja UN AudioBufferSourceNode a la vez. play(buffer)
//     reemplaza al anterior (lo detiene + descarta).
//   - El AnalyserNode es persistente: vive con el AudioContext y se reutiliza
//     entre canciones. Sus parámetros vienen del doc 07 §D.1, pineados.
//   - getFrequencyData(N) hace un downsample lineal del buffer FFT nativo
//     (1024 bins porque fftSize=2048) al N que el shader necesita (256).
//   - getRMS() opera sobre el buffer time-domain — más estable que tomar
//     amplitud del FFT y barato (suma de cuadrados sobre 2048 muestras).
//   - onstatechange dispatch `audio:pause` / `audio:resume` en window. El
//     engine.js los escucha para frenar el ticker (doc 07 §C.3).

const FFT_SIZE = 2048;             // doc 07 §D.1 — pineado.
const SMOOTHING = 0.82;
const MIN_DB = -100;
const MAX_DB = -30;

export class AudioEngine {
    constructor() {
        this.ctx = null;
        this.analyser = null;
        this.source = null;
        this.gainNode = null;
        this._freqBuffer = null;     // Uint8Array(FFT_SIZE/2) — 1024 bins
        this._timeBuffer = null;     // Uint8Array(FFT_SIZE) — 2048 muestras
        this._currentBuffer = null;  // último AudioBuffer reproducido (para test)
        this._playStartedAt = 0;     // ctx.currentTime cuando arrancó el play actual
        this._onVisibilityChange = null;
    }

    /** Crea el AudioContext. Idempotente. Devuelve el state ('running' o 'suspended'). */
    async start() {
        if (this.ctx !== null) return this.ctx.state;

        // Some browsers require explicit `latencyHint: 'interactive'` for low
        // latency in games. Default es 'interactive' actualmente, pero lo
        // dejamos explícito para que sobreviva a cambios futuros.
        this.ctx = new (window.AudioContext || window.webkitAudioContext)({
            latencyHint: 'interactive',
        });

        this.analyser = this.ctx.createAnalyser();
        this.analyser.fftSize = FFT_SIZE;
        this.analyser.smoothingTimeConstant = SMOOTHING;
        this.analyser.minDecibels = MIN_DB;
        this.analyser.maxDecibels = MAX_DB;

        this.gainNode = this.ctx.createGain();
        this.gainNode.gain.value = 1.0;

        // analyser -> gain -> destination. El BufferSource va al analyser.
        this.analyser.connect(this.gainNode);
        this.gainNode.connect(this.ctx.destination);

        this._freqBuffer = new Uint8Array(this.analyser.frequencyBinCount);
        this._timeBuffer = new Uint8Array(FFT_SIZE);

        // Pause/resume del engine cuando el SO suspende el contexto. Los
        // navegadores en escritorio NO suspenden auto al cambiar de pestaña
        // — solo en casos extremos (lock screen, ahorro batería, fullscreen
        // exit en algunas combinaciones). Este handler es la red defensiva
        // para esos casos.
        this.ctx.addEventListener('statechange', () => {
            if (this.ctx.state === 'suspended' || this.ctx.state === 'interrupted') {
                window.dispatchEvent(new CustomEvent('audio:pause'));
            } else if (this.ctx.state === 'running') {
                window.dispatchEvent(new CustomEvent('audio:resume'));
            }
        });

        // Page Visibility API — pausamos manualmente al cambiar de pestaña.
        // Razón: Daino es un juego rítmico; si la pestaña pierde foco mientras
        // la canción sigue, los obstáculos del Bloque 5+ se desincronizarán
        // del beat. Pausar el AudioContext congela el reloj de la canción y
        // todo se mantiene alineado al volver. statechange dispatcheará los
        // eventos audio:pause/resume gracias al handler de arriba.
        this._onVisibilityChange = () => {
            if (this.ctx === null) return;
            if (document.hidden && this.ctx.state === 'running') {
                this.ctx.suspend().catch(() => {});
            } else if (!document.hidden && this.ctx.state === 'suspended') {
                this.ctx.resume().catch(() => {});
            }
        };
        document.addEventListener('visibilitychange', this._onVisibilityChange);

        // Si arrancó suspended (autoplay policy), intentar resume — el drop
        // que llamó a start() ya cuenta como gesture, así que debería pasar.
        if (this.ctx.state === 'suspended') {
            try { await this.ctx.resume(); } catch { /* el caller verá state */ }
        }

        return this.ctx.state;
    }

    /** Decodifica un ArrayBuffer (de FileReader) a AudioBuffer. */
    async decode(arrayBuffer) {
        if (this.ctx === null) await this.start();
        // decodeAudioData consume el ArrayBuffer en algunos navegadores; pasar
        // el original es OK porque upload.js no lo reutiliza después.
        return await this.ctx.decodeAudioData(arrayBuffer);
    }

    /**
     * Reproduce un AudioBuffer desde el inicio. Si había uno sonando, lo
     * detiene primero. Devuelve un handle con stop().
     */
    play(audioBuffer) {
        if (this.ctx === null) {
            throw new Error('AudioEngine.play llamado antes de start()');
        }

        if (this.source !== null) {
            try { this.source.stop(); } catch { /* ya parado */ }
            this.source.disconnect();
            this.source = null;
        }

        const src = this.ctx.createBufferSource();
        src.buffer = audioBuffer;
        src.connect(this.analyser);
        src.start(0);

        this.source = src;
        this._currentBuffer = audioBuffer;
        // Stamp del reloj del ctx en el momento del start. getAudioTime()
        // resta esto a ctx.currentTime para devolver el "song time".
        this._playStartedAt = this.ctx.currentTime;

        return {
            stop: () => {
                try { src.stop(); } catch { /* idempotente */ }
                src.disconnect();
                if (this.source === src) this.source = null;
            },
        };
    }

    /**
     * Devuelve N bins frecuencia [0..255] downsampled del buffer FFT nativo.
     * Si N coincide con frequencyBinCount no hace copia extra; si es menor,
     * promedia segmentos contiguos.
     */
    getFrequencyData(targetBins = 256) {
        if (this.analyser === null) {
            // Devolver buffer idle del tamaño pedido si no se ha iniciado aún.
            return new Uint8Array(targetBins);
        }
        this.analyser.getByteFrequencyData(this._freqBuffer);

        const native = this._freqBuffer.length;  // 1024 con fftSize=2048
        if (targetBins === native) return this._freqBuffer;

        const out = new Uint8Array(targetBins);
        const step = native / targetBins;
        for (let i = 0; i < targetBins; i++) {
            const start = (i * step) | 0;
            const end = Math.min(((i + 1) * step) | 0, native);
            let sum = 0;
            const n = end - start;
            for (let j = start; j < end; j++) sum += this._freqBuffer[j];
            out[i] = n > 0 ? (sum / n) | 0 : 0;
        }
        return out;
    }

    /** RMS [0,1] sobre el último frame de time-domain data. */
    getRMS() {
        if (this.analyser === null) return 0;
        this.analyser.getByteTimeDomainData(this._timeBuffer);
        let sumSq = 0;
        const len = this._timeBuffer.length;
        for (let i = 0; i < len; i++) {
            const v = (this._timeBuffer[i] - 128) / 128;  // re-centrar a [-1, 1]
            sumSq += v * v;
        }
        const rms = Math.sqrt(sumSq / len);
        // Compresión suave: el RMS bruto pocas veces pasa de 0.4 incluso en
        // música fuerte. Empujamos a [0,1] para que el shader tenga rango útil.
        return Math.min(1, rms * 2.4);
    }

    /** Estado actual del contexto, o 'closed' si nunca se inició. */
    get state() {
        return this.ctx?.state ?? 'closed';
    }

    /**
     * Último AudioBuffer reproducido (o null). Bloque 5 lo usa el
     * LevelGenerator si hace falta releer el buffer sin pasarlo a mano.
     */
    get currentBuffer() {
        return this._currentBuffer;
    }

    /**
     * Reloj relativo a cuándo arrancó el play() actual. Si no hay source o
     * el ctx aún no existe, devuelve 0. La GameSession lo consume cada tick
     * para alimentar al spawner — la única fuente de verdad temporal.
     */
    getAudioTime() {
        if (this.ctx === null || this.source === null) return 0;
        // ctx.currentTime avanza siempre que el contexto está running. El
        // source.start(0) del play() lo arrancó en este reloj, así que el
        // delta directo es el tiempo de canción reproducido.
        const t = this.ctx.currentTime - this._playStartedAt;
        return t < 0 ? 0 : t;
    }
}
