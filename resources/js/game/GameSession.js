// GameSession — orquestador de un run del juego. Recibe el engine (Pixi),
// el AudioEngine y el Level pre-computado, y conecta todas las piezas:
// monta Dino + spawner + HUD, suscribe Input, registra el tick en el ticker
// global de Pixi, escucha pause/resume del audio.
//
// Una sola sesión activa a la vez. `start()` arranca; `stop({reason})`
// limpia. Reiniciar = stop() + new GameSession(...). Bloque 6 podrá pulir
// el "press SPACE to restart" inline; aquí ofrecemos el hook básico.

import { Dino } from './entities/Dino.js';
import { createSpawner } from './systems/spawner.js';
import { createHud } from '../ui/hud.js';
import { Input } from './systems/input.js';
import { computeGroundY, integrateDino, findCollision } from './systems/physics.js';
import { DT_CAP_S } from './config.js';

export class GameSession {
    /**
     * @param {object} cfg
     * @param {ReturnType<import('./engine.js').startEngine> extends Promise<infer T> ? T : never} cfg.engine
     * @param {import('./audio/AudioEngine.js').AudioEngine} cfg.audioEngine
     * @param {import('./levels/LevelGenerator.js').Level} cfg.level
     * @param {() => void} [cfg.onGameOver]   Callback cuando el run termina (lose/win).
     */
    constructor(cfg) {
        this.engine = cfg.engine;
        this.audioEngine = cfg.audioEngine;
        this.level = cfg.level;
        this.onGameOver = cfg.onGameOver ?? (() => {});

        this.dino = null;
        this.spawner = null;
        this.hud = null;
        this._jumpHandler = null;
        this._pauseHandler = null;
        this._resumeHandler = null;
        this._ticker = null;
        this._tickFn = null;
        this._isPaused = false;
        this._isRunning = false;
        this._score = 0;
    }

    start() {
        if (this._isRunning) return;
        this._isRunning = true;

        const layers = this.engine.getLayers();
        const ticker = this.engine.getTicker();
        if (!layers || !ticker) {
            throw new Error('GameSession.start: engine sin layers/ticker — ¿startEngine se completó?');
        }
        this._ticker = ticker;

        const groundY = computeGroundY(window.innerHeight);

        // Dino.
        this.dino = new Dino();
        this.dino.y = groundY - this.dino.height;
        layers.gameLayer.addChild(this.dino);

        // Spawner.
        this.spawner = createSpawner({
            timeline: this.level.timeline,
            gameLayer: layers.gameLayer,
            viewportWidth: window.innerWidth,
            groundY,
        });

        // HUD ahora vive en DOM (#hud-root) — Bloque 6 Lote D. El layer
        // hudLayer del canvas se mantiene vacío reservado para flashes
        // visuales futuros (perfect-beat ring, damage flash).
        this.hud = createHud();
        this.hud.update(0, 0, this.level.durationSec);
        if (this.level.sourceName) this.hud.setSong(this.level.sourceName);

        // Input.
        Input.setup();
        this._jumpHandler = () => {
            if (!this._isRunning || this._isPaused) return;
            this.dino.jump();
        };
        Input.on('jump', this._jumpHandler);

        // Pausa/resume vienen del AudioEngine (statechange + Page Visibility).
        // Cuando el contexto suspende, congelamos lógica del juego — el ticker
        // de Pixi sigue corriendo (el shader del Bloque 4 también lo respeta
        // internamente), pero nuestro tick del juego ignora el frame.
        this._pauseHandler = () => { this._isPaused = true; };
        this._resumeHandler = () => { this._isPaused = false; };
        window.addEventListener('audio:pause', this._pauseHandler);
        window.addEventListener('audio:resume', this._resumeHandler);

        // Ticker.
        this._tickFn = (t) => this._tick(t);
        ticker.add(this._tickFn);
    }

    _tick(tickerArg) {
        if (!this._isRunning || this._isPaused) return;

        // Cap del dt — al volver de bg el deltaMS puede ser enorme y meter
        // al Dino bajo el suelo de un salto. DT_CAP_S protege la integración.
        const dt = Math.min(DT_CAP_S, tickerArg.deltaMS / 1000);

        const audioTime = this.audioEngine.getAudioTime();

        // Físicas del Dino primero (gravedad + clamp al suelo).
        const groundY = computeGroundY(window.innerHeight);
        integrateDino(this.dino, dt, groundY);

        // Spawner: instancia nuevos obstáculos y mueve los activos.
        this.spawner.tick(audioTime, dt, this.level.gameSpeed);

        // Colisión Dino vs obstáculos activos.
        const hit = findCollision(this.dino, this.spawner.getActive());
        if (hit !== null) {
            this._end('collision');
            return;
        }

        // Score: por ahora, score = floor(audioTime * 100). Bloque 6 puede
        // afinar (combo, BPM, aciertos exactos al beat). Mantengo simple.
        this._score = Math.floor(audioTime * 100);
        this.hud.update(this._score, audioTime, this.level.durationSec);

        // Win condition: la canción terminó.
        if (audioTime >= this.level.durationSec) {
            this._end('win');
        }
    }

    _end(reason) {
        if (!this._isRunning) return;
        this._isRunning = false;

        // Detener el ticker propio (el de Pixi sigue para el shader).
        if (this._ticker && this._tickFn) {
            this._ticker.remove(this._tickFn);
        }

        // Detener el audio: el shader sigue, pero la canción se corta. El
        // user puede soltar otro MP3 para empezar de nuevo.
        try {
            if (this.audioEngine.source) this.audioEngine.source.stop();
        } catch { /* idempotente */ }

        const message = reason === 'win'
            ? `WIN  SCORE ${this._score}`
            : `GAME OVER  SCORE ${this._score}`;
        this.hud.setMessage(message);

        // Bloque 6: dispatch desacoplado para que el AppController orqueste
        // la transición a MENU sin acoplarse al callback. `onGameOver` se
        // mantiene como fallback para callers que prefieran el camino directo.
        window.dispatchEvent(new CustomEvent('daino:gamestate', {
            detail: { kind: reason, score: this._score, durationSec: this.level.durationSec },
        }));

        this.onGameOver({ reason, score: this._score });
    }

    stop() {
        if (this._isRunning) this._end('stopped');
        // Despide listeners.
        if (this._jumpHandler) Input.off('jump', this._jumpHandler);
        if (this._pauseHandler) window.removeEventListener('audio:pause', this._pauseHandler);
        if (this._resumeHandler) window.removeEventListener('audio:resume', this._resumeHandler);

        if (this.spawner) this.spawner.dispose();
        if (this.hud) this.hud.dispose();
        if (this.dino) {
            const layers = this.engine.getLayers();
            if (layers) layers.gameLayer.removeChild(this.dino);
            this.dino.destroy({ children: true });
        }

        this.dino = null;
        this.spawner = null;
        this.hud = null;
    }
}
