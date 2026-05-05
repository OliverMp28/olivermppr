// Background shader audio-reactivo de Daino — Bloque 4.
//
// Capa 1 del z-stack visual (doc 06 §3): cubre el 100% del viewport, debajo
// del gameplay y del HUD. Recibe FFT como textura 256x1 (sampler2D uFFT),
// no como uniforms individuales — saturaría el bus uniform si fueran 1024
// floats sueltos (doc 07 §D.2, doc 09 §2.4).
//
// Uniforms agrupados en `audioUniforms` (Pixi v8 los compila a un UBO):
//   uShaderMode — 0=idle (menú), 1=reactive (en partida con audio). En idle
//                 el shader IGNORA todos los uniforms de audio (FFT, bands,
//                 BPM): pinta solo el degradado base + ondas suaves + vignette.
//                 Hace el reset robusto — al cambiar de modo no dependemos
//                 de que cada uniform band quede en 0 (Bloque 6 Lote E).
//   uTime      — segundos de reloj global; siempre avanza, también en idle.
//   uRMS       — energía total normalizada [0,1].
//   uBass      — energía banda grave (bins 0..10).
//   uMid       — energía banda media (bins 10..100).
//   uHigh      — energía banda aguda (bins 100..256).
//   uBpmPulse  — pulse cuadrado [0,1] cableado en Bloque 5 al BPM detectado.
//
// El shader es deliberadamente sencillo en este bloque — el objetivo es
// validar el pipeline audio→GPU. La estética se afina luego con HMR (sin
// recargar el audio gracias a vite-plugin-glsl + import.meta.hot.accept).

in vec2 vTextureCoord;
out vec4 finalColor;

uniform sampler2D uTexture; // input del filtro (lo inyecta Pixi). Lo ignoramos
                            // a propósito: pintamos el fondo desde cero.
uniform sampler2D uFFT;     // textura 256x1, formato r8unorm.

uniform float uShaderMode;  // 0=idle, 1=reactive
uniform float uTime;
uniform float uRMS;
uniform float uBass;
uniform float uMid;
uniform float uHigh;
uniform float uBpmPulse;
uniform float uDebugMode;   // 0=normal, 1=fft crudo, 2=uv coords, 3=uniforms

// Paleta — espejo de los tokens de @theme en main.css (mantener sincronizados).
const vec3 COLOR_BG_DEEP   = vec3(0.035, 0.035, 0.060);
const vec3 COLOR_BG_LIFTED = vec3(0.090, 0.060, 0.140);
const vec3 COLOR_BEAT      = vec3(0.880, 0.220, 0.380);
const vec3 COLOR_HIGH      = vec3(0.560, 0.880, 1.000);

void main(void) {
    vec2 uv = vTextureCoord;

    // ---------- MODOS DEBUG (activables con ?debug=N en la URL) ----------
    // Mode 1 — pinta el sample crudo del uFFT como rojo. Si en idle se ve
    //   negro: la textura se sampleó como ceros (correcto).
    //   rojo intenso: la GPU lee basura no-cero (problema de init/format).
    //   rayas verticales: el FFT tiene picos puntuales pero la base es 0.
    if (uDebugMode > 0.5 && uDebugMode < 1.5) {
        float bin = texture(uFFT, vec2(uv.x, 0.5)).r;
        finalColor = vec4(bin, bin * 0.4, 0.0, 1.0);
        return;
    }
    // Mode 2 — pinta uv.x en rojo, uv.y en verde. Esquina sup-izq debe ser
    //   negra (uv≈0,0), sup-der roja (uv.x≈1), inf-izq verde, inf-der amarilla.
    //   Si uv.y está flipped (esq sup-izq verde), el shader tiene la Y al revés
    //   y muchos cálculos de gradiente/FFT van invertidos.
    if (uDebugMode > 1.5 && uDebugMode < 2.5) {
        finalColor = vec4(uv.x, uv.y, 0.0, 1.0);
        return;
    }
    // Mode 3 — pinta los uniforms escalares como bandas verticales.
    //   Banda 0..0.2 = uTime/10 (cíclico), 0.2..0.4 = uRMS, 0.4..0.6 = uBass,
    //   0.6..0.8 = uMid, 0.8..1.0 = uHigh. Sirve para ver que los uniforms
    //   llegan al shader.
    if (uDebugMode > 2.5) {
        float v = 0.0;
        if      (uv.x < 0.2) v = mod(uTime, 1.0);
        else if (uv.x < 0.4) v = uRMS;
        else if (uv.x < 0.6) v = uBass;
        else if (uv.x < 0.8) v = uMid;
        else                 v = uHigh;
        finalColor = vec4(v, v * 0.5, 1.0 - v, 1.0);
        return;
    }
    // ---------- /DEBUG ----------

    // ---------- BASE — visible en idle y reactive ----------
    // Gradiente vertical: deep abajo, lifted arriba.
    float gradient = smoothstep(0.0, 1.0, uv.y);
    vec3 base = mix(COLOR_BG_DEEP, COLOR_BG_LIFTED, gradient);

    // Ondas senoidales muy tenues con uTime. NO dependen de audio.
    float wave = sin(uv.x * 16.0 + uTime * 1.2) * 0.5 + 0.5;
    wave += sin(uv.y * 24.0 - uTime * 0.7) * 0.25;
    base += vec3(wave) * 0.04;

    // ---------- REACTIVE — solo cuando hay audio atachado ----------
    // Branching explícito en el shader. Esto hace el reset trivial: al pasar
    // a idle, basta con bajar uShaderMode a 0 — los uniforms band pueden
    // quedar con cualquier valor sin contaminar el render.
    if (uShaderMode > 0.5) {
        // 1. Bass enriquece el gradiente con tinte beat.
        base += COLOR_BEAT * uBass * 0.18;

        // 2. uRMS amplifica las ondas senoidales sobre la base ya pintada.
        base += vec3(wave) * (uRMS * 0.18);

        // 3. Barras FFT desde arriba.
        float bin = texture(uFFT, vec2(uv.x, 0.5)).r;
        float barHeight = bin * 0.35;
        float bar = step(uv.y, barHeight);
        base = mix(base, COLOR_BEAT, bar * 0.55);

        // 4. Brillo de agudos — destellos sutiles arriba.
        float topGlow = smoothstep(0.65, 1.0, uv.y) * uHigh * 0.4;
        base += COLOR_HIGH * topGlow;

        // 5. BPM pulse — todo el frame brilla un instante en cada beat.
        base *= 1.0 + uBpmPulse * 0.15;
    }

    // Vignette suave para dar foco al gameplay (capa 3 vive encima).
    // Visible en ambos modos para que el menú también se sienta envolvente.
    float dx = uv.x - 0.5;
    float dy = uv.y - 0.5;
    float vignette = 1.0 - smoothstep(0.4, 0.95, sqrt(dx * dx + dy * dy));
    base *= mix(0.7, 1.0, vignette);

    finalColor = vec4(base, 1.0);
}
