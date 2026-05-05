// HUD del Bloque 5: score, tiempo y barra de progreso, dentro del canvas.
//
// Por qué BitmapText (y no `Text`):
//   - Doc 07 §A.3 🚨: `Text` regenera textura por cambio. A 60fps con un
//     score que sube cada frame eso destroza la GPU. `BitmapText` reusa
//     glyphs ya rasterizados de un atlas instalado una vez.
//
// La fuente (`DainoHud`) la genera Pixi v8 desde una FontFace nativa con
// `BitmapFont.install` — no necesitamos un .fnt binario. `chars` solo
// incluye los glifos que vamos a pintar para minimizar el atlas.

import { BitmapFont, BitmapText, Container, Graphics } from 'pixi.js';
import { HUD } from '../config.js';

let fontInstalled = false;

function ensureFont() {
    if (fontInstalled) return;
    BitmapFont.install({
        name: HUD.fontName,
        style: {
            fontFamily: 'monospace',
            fontSize: HUD.fontSize,
            fill: HUD.fontColor,
        },
        // resolution alta = glyphs nítidos en HiDPI. Pixi escala el atlas.
        resolution: window.devicePixelRatio || 1,
        chars: HUD.chars,
    });
    fontInstalled = true;
}

/**
 * Crea el HUD y lo añade al hudLayer. Devuelve un objeto con `update()`,
 * `dispose()` y `setMessage()` (mensaje grande centrado para "Game Over").
 *
 * @param {{ addChild: Function, removeChild: Function }} hudLayer
 */
export function createHud(hudLayer) {
    ensureFont();

    const root = new Container({ label: 'hud-root' });
    hudLayer.addChild(root);

    const scoreText = new BitmapText({
        text: 'SCORE 000000',
        style: { fontFamily: HUD.fontName, fontSize: HUD.fontSize, fill: HUD.fontColor },
    });
    scoreText.x = HUD.padding;
    scoreText.y = HUD.padding;
    root.addChild(scoreText);

    const timeText = new BitmapText({
        text: '0:00 / 0:00',
        style: { fontFamily: HUD.fontName, fontSize: HUD.fontSize, fill: HUD.fontColor },
    });
    // X se recalcula en layout() porque depende de viewportWidth.
    timeText.x = 0;
    timeText.y = HUD.padding;
    root.addChild(timeText);

    // Barra de progreso bajo el reloj — Graphics simple, redibujamos
    // proporcional cada frame. Queda discreta hasta que se afine la estética.
    const progressBg = new Graphics();
    const progressFg = new Graphics();
    root.addChild(progressBg);
    root.addChild(progressFg);

    // Mensaje grande centrado (game over / win). Oculto por defecto.
    const message = new BitmapText({
        text: '',
        style: { fontFamily: HUD.fontName, fontSize: HUD.fontSize * 1.5, fill: HUD.fontColor },
    });
    message.anchor = { x: 0.5, y: 0.5 };
    message.visible = false;
    root.addChild(message);

    let lastScore = -1;
    let lastTimeKey = '';

    function layout() {
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        // Reposicionar timeText pegado al borde derecho.
        timeText.x = vw - HUD.padding - timeText.width;
        timeText.y = HUD.padding;

        // Barra de progreso bajo el timeText, pegada al borde derecho con un
        // ancho mínimo legible. Si el viewport es muy estrecho, encoge.
        const barWidth = Math.min(360, vw - 2 * HUD.padding);
        const barHeight = 6;
        const barX = vw - HUD.padding - barWidth;
        const barY = HUD.padding + HUD.fontSize + 8;

        progressBg
            .clear()
            .rect(barX, barY, barWidth, barHeight)
            .fill({ color: 0xffffff, alpha: 0.18 });

        // El fg se redibuja cada vez que update() avanza — guardamos coords
        // como propiedades del objeto para que update() las pueda leer sin
        // tener que re-pasarlas.
        progressFg._barX = barX;
        progressFg._barY = barY;
        progressFg._barW = barWidth;
        progressFg._barH = barHeight;

        // Mensaje al centro.
        message.x = vw / 2;
        message.y = vh / 2;
    }
    layout();
    window.addEventListener('resize', layout);

    function fmtTime(sec) {
        const s = Math.max(0, Math.floor(sec));
        const m = Math.floor(s / 60);
        const ss = String(s % 60).padStart(2, '0');
        return `${m}:${ss}`;
    }

    return {
        /**
         * @param {number} score        Entero, monótono creciente durante la partida.
         * @param {number} currentSec   Tiempo de canción actual.
         * @param {number} totalSec     Duración total.
         */
        update(score, currentSec, totalSec) {
            // Solo escribimos si el valor cambió — `BitmapText.text =` regenera
            // el layout interno, así que evitar trabajos redundantes ayuda.
            if (score !== lastScore) {
                scoreText.text = `SCORE ${String(score).padStart(6, '0')}`;
                lastScore = score;
            }
            const timeKey = `${fmtTime(currentSec)}/${fmtTime(totalSec)}`;
            if (timeKey !== lastTimeKey) {
                timeText.text = `${fmtTime(currentSec)} / ${fmtTime(totalSec)}`;
                // Re-layout solo cuando cambia el ancho probablemente —
                // como el formato es estable, su ancho varía poco. Para
                // simplificar, recolocamos en cada cambio.
                timeText.x = window.innerWidth - HUD.padding - timeText.width;
                lastTimeKey = timeKey;
            }

            const ratio = totalSec > 0 ? Math.max(0, Math.min(1, currentSec / totalSec)) : 0;
            progressFg
                .clear()
                .rect(progressFg._barX, progressFg._barY, progressFg._barW * ratio, progressFg._barH)
                .fill({ color: HUD.fontColor, alpha: 0.85 });
        },
        setMessage(text) {
            message.text = text;
            message.visible = text !== '';
            // Recentrar tras cambio de texto (anchor ya en 0.5/0.5, solo
            // reasignar X/Y por si hubo resize entre medias).
            message.x = window.innerWidth / 2;
            message.y = window.innerHeight / 2;
        },
        dispose() {
            window.removeEventListener('resize', layout);
            hudLayer.removeChild(root);
            root.destroy({ children: true });
        },
    };
}
