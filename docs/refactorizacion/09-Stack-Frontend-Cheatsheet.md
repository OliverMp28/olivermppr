# 09 — Stack Frontend Cheatsheet

> Este doc es **referencia técnica** del stack frontend. **NO sobreescribe** decisiones arquitectónicas de docs **04/05/06/07**. Si en algo contradice esos docs, ellos mandan.
>
> Versiones del proyecto Daino al 28 abril 2026:
> `vite@8.0.10` · `@tailwindcss/vite@4.2.4` · `tailwindcss@4.2.4` · `pixi.js@8.18.1` · `bun@1.2.3`.
>
> Ámbito: shell SSR desde PHP-FPM con manifest de Vite (no SPA), canvas a viewport completo con PixiJS, shaders GLSL audio-reactivos, JS vanilla con ES Modules (sin React/Vue).

---

## 1. Vite 8

Vite 8 (estable desde 12-mar-2026) es el cambio arquitectónico más grande desde Vite 2: **Rolldown** (Rust) sustituye a esbuild + Rollup como bundler unificado, y **Oxc** sustituye a esbuild para transformaciones JS y minificación. El comportamiento de dev server y de plugin API se mantiene compatible para la mayoría de plugins, pero hay varios sitios donde la migración pellizca.

### 1.1 Qué cambia frente a Vite 6/7

| Área | Vite 6 / 7 | Vite 8.0.x |
|---|---|---|
| Dev bundler | esbuild | Rolldown |
| Build bundler | Rollup | Rolldown |
| JS transforms | esbuild | Oxc |
| JS minify | esbuild | Oxc Minifier |
| CSS minify | esbuild (default) | **Lightning CSS (default)** |
| Manifest path | `dist/manifest.json` | **`dist/.vite/manifest.json`** (cambio efectivo desde Vite 5) |
| `build.rollupOptions` | API principal | Deprecated (alias a `build.rolldownOptions`) |
| `optimizeDeps.esbuildOptions` | API principal | Deprecated (alias a `optimizeDeps.rolldownOptions`) |
| Tamaño install | — | ~15 MB más (~10 MB lightningcss + ~5 MB Rolldown) |

Documentado como NRV (no-rolldown-vite) en la guía de migración: solo aplica a quien venía de Vite 7 normal y no del paquete técnico `rolldown-vite`.

### 1.2 `build.target` por defecto

Vite 8 actualiza el target por defecto (`'baseline-widely-available'`) a navegadores publicados ~2.5 años antes de 2026-01-01:

| Browser | Vite 6/7 | Vite 8 |
|---|---|---|
| Chrome | 107 | **111** |
| Edge | 107 | **111** |
| Firefox | 104 | **114** |
| Safari | 16.0 | **16.4** |

Implicación práctica para Daino: si necesitamos compatibilidad con navegadores anteriores (p. ej. Safari 15 en iOS viejos), hay que fijar explícitamente `build.target` a una lista más permisiva. El default de Vite 8 implica que el código generado puede usar features que rompen en Safari 16.0–16.3.

### 1.3 Configuración para integración PHP-FPM (sin SPA)

Patrón canónico para shell SSR con `vite build` + manifest:

```js
// vite.config.js
import { defineConfig } from 'vite';
import glsl from 'vite-plugin-glsl';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  base: '/dist/',                    // debe coincidir con el path público servido por nginx
  plugins: [tailwindcss(), glsl()],
  server: {
    cors: { origin: 'http://daino.local' },  // backend PHP origin
    strictPort: true,
    port: 5173,
  },
  build: {
    manifest: true,                  // genera dist/.vite/manifest.json
    outDir: 'public/dist',
    rollupOptions: {                 // renombrado a rolldownOptions, pero el alias sigue funcionando
      input: {
        main: 'src/main.js',
        hud:  'src/hud.js',
      },
    },
  },
});
```

#### Ubicación del manifest

Desde Vite 5 la ruta canónica es `<outDir>/.vite/manifest.json` (con punto). Vite 7 y 8 mantienen ese path. Cualquier integración PHP que lea desde `dist/manifest.json` (sin el `.vite/`) viene de docs antiguos y romperá silenciosamente.

#### Lectura desde PHP

Patrón mínimo en PHP-FPM para emitir tags correctos:

```php
<?php
$dev = getenv('APP_ENV') === 'dev';
$base = '/dist/';

if ($dev) {
    // dev: cargar el cliente HMR + entry directamente del dev server
    echo '<script type="module" src="http://localhost:5173/@vite/client"></script>';
    echo '<script type="module" src="http://localhost:5173/src/main.js"></script>';
} else {
    $manifest = json_decode(
        file_get_contents(__DIR__ . '/../public/dist/.vite/manifest.json'),
        true
    );
    $entry = $manifest['src/main.js'];

    foreach ($entry['css'] ?? [] as $css) {
        echo '<link rel="stylesheet" href="' . $base . $css . '">';
    }
    echo '<script type="module" src="' . $base . $entry['file'] . '"></script>';
    foreach ($entry['imports'] ?? [] as $imp) {
        echo '<link rel="modulepreload" href="' . $base . $manifest[$imp]['file'] . '">';
    }
}
```

Para implementaciones más completas existen librerías como `mindplay/php-vite` y `userfrosting/vite-php-twig` que ya consumen `.vite/manifest.json`, manejan `preload`, `imports` recursivos y assets estáticos. Útiles como referencia aunque no se adopten.

#### Polyfill de modulepreload

Si no se desactiva, Vite 8 sigue inyectando el polyfill. En modo backend integration **hay que importarlo manualmente** al principio del entry:

```js
// src/main.js
import 'vite/modulepreload-polyfill';
```

#### nginx delante en producción

- Servir `public/dist/` como estáticos con cache largo (`Cache-Control: public, max-age=31536000, immutable`) — los hashes en los nombres de archivo se encargan de la invalidación.
- `base` en `vite.config.js` debe coincidir con la ruta pública en nginx (p. ej. `/dist/`). Si nginx sirve desde `/static/dist/`, hay que cambiar `base` consecuentemente.
- En dev no hay manifest; el servidor de Vite (5173) sirve los módulos directamente. nginx puede proxyearlo, pero es más simple apuntar el `<script>` al dev server desde PHP en modo dev.

### 1.4 ⭐ HMR de fragment shaders GLSL

**Esta es la sección más crítica para Bloque 5** (shaders audio-reactivos en filtros de PixiJS).

Vite no entiende `.frag`/`.glsl` por defecto — los importa como string si se usa el sufijo `?raw`, pero entonces no hay HMR significativo (recarga completa de página). Para HMR real de shaders, el plugin de referencia es **`vite-plugin-glsl`** (UstymUkhman).

#### Capacidades relevantes (a abril 2026)

| Versión | Cambio |
|---|---|
| 0.5.0 | Hot reload de shaders cuando `watch: true` |
| 1.1.x | Migra a `handleHotUpdate`; agrega chunks al `moduleGraph` interno (PR #34) |
| 1.2.0+ | Compatible con `vite^5` |
| 1.3.1+ | Compatible con `vite^6` |
| 1.5.0+ | Permite `importKeyword` custom |
| 1.6.0+ | Soporte experimental Slang via `onComplete` |

Al 28-abr-2026 no hay un release con bandera explícita "Vite 8 supported", pero los reportes de uso indican que **el plugin funciona con Vite 8 sin cambios** porque la API de `handleHotUpdate` no rompió en Vite 8. Verificar al subir.

#### Configuración recomendada para Daino

```js
// vite.config.js
import glsl from 'vite-plugin-glsl';

export default defineConfig({
  plugins: [
    glsl({
      include: ['**/*.glsl', '**/*.frag', '**/*.vert', '**/*.fs', '**/*.vs'],
      defaultExtension: 'glsl',
      warnDuplicatedImports: true,
      removeDuplicatedImports: true,
      compress: false,    // dejar que Oxc/Lightning minimice en build si interesa
      watch: true,        // HMR activado
      root: '/',
    }),
  ],
});
```

```js
// src/shaders/myFilter.js
import fragment from './audio-react.frag';
// fragment es un string. Cualquier `#include foo.frag` es resuelto por el plugin.
```

#### Cómo encaja con un filtro PixiJS v8

Los filtros en PixiJS v8 se construyen pasando shaders por `glProgram` (WebGL) o `gpuProgram` (WebGPU). El plugin solo entrega el string final compilado; **el HMR de Vite no recompila el filtro de PixiJS automáticamente**. Hay que aceptar el update manualmente en el módulo que crea el filtro:

```js
// src/shaders/audioFilter.js
import { Filter, GlProgram } from 'pixi.js';
import fragment from './audio-react.frag';
import vertex from './default.vert';

export const audioFilter = new Filter({
  glProgram: new GlProgram({ fragment, vertex, name: 'audio-react' }),
  resources: {
    audioUniforms: { uTime: { value: 0, type: 'f32' } },
    uFFT: fftTexture,   // sampler2D, ver §2.4
  },
});

if (import.meta.hot) {
  import.meta.hot.accept('./audio-react.frag', (mod) => {
    // Sustituir el GlProgram en caliente
    audioFilter.glProgram = new GlProgram({
      fragment: mod.default,
      vertex,
      name: 'audio-react',
    });
  });
}
```

Sin ese `import.meta.hot.accept`, guardar el `.frag` recargará la página completa y se perderá el estado de la pista de audio actual.

#### Issues conocidas

- **Recarga de página completa cuando se quería patch en caliente**: tras la migración de `configureServer` → `handleHotUpdate` (issue #31 del repo) hay reportes de que en algunos setups el plugin fuerza reload completo. La solución defensiva es estructurar el módulo del filtro para tolerar full reload sin perder estado crítico (pasar tiempo absoluto de audio, no relativo).
- **Chunks en `moduleGraph`**: hasta 1.1.2 los `#include foo.glsl` no estaban en el module graph, así que cambios en chunks no disparaban HMR del archivo importador. Resuelto en PR #34. **Verificar que la versión instalada sea ≥ 1.1.2**.
- **Pure ESM desde 0.3.0**: requiere `"type": "module"` en `package.json` o el equivalente en TS.
- **Imports recursivos** lanzan error explícito; los duplicados generan warning salvo que `removeDuplicatedImports: true`.

### 1.5 Otros cambios de Vite 8 que pellizcan

- **`import.meta.hot.accept` con URL** ya no se soporta — usar id (issue #21382). Cambio efectivo desde Vite 8.0.0.
- **CommonJS interop consistente**: el `default` de un módulo CJS ahora se resuelve de manera unificada (ver Rolldown docs `bundling-cjs#ambiguous-default-import-from-cjs-modules`). Si algún paquete antiguo deja de funcionar, opción de escape: `legacy.inconsistentCjsInterop: true` (deprecated, temporal).
- **`require` para módulos externalizados** se preserva como `require` y no se convierte a `import`. Para volver al comportamiento previo: `esmExternalRequirePlugin` exportado por `vite`.
- **`output.manualChunks` en forma de objeto eliminado**, en forma de función deprecated. Migrar a `codeSplitting` de Rolldown.
- **Module type auto-detect** (Rolldown): plugins que en `load`/`transform` convierten otro tipo a JS deben devolver `moduleType: 'js'` explícito.
- **`build.commonjsOptions`** ahora es no-op.
- **`esbuild` ya no es dependencia directa**. Si un plugin propio usa `transformWithEsbuild`, hay que instalar `esbuild` como `devDependency` o migrar a `transformWithOxc`.

### 1.6 Cosas que NO hacer (Vite 8)

- ❌ Leer el manifest desde `dist/manifest.json` — está en `dist/.vite/manifest.json` desde Vite 5.
- ❌ Asumir que el navegador objetivo es Safari 15 con el `target` por defecto: subió a Safari 16.4.
- ❌ Pasar URL a `import.meta.hot.accept`. Pasar id.
- ❌ Configurar minificación con `esbuild.*` y suponer que se aplica: las opciones equivalentes están bajo `build.rolldownOptions.output.minify` y `oxc.*`. Las de esbuild están deprecated y traducidas automáticamente, pero no todas tienen equivalencia (p. ej. `mangleProps` no existe en Oxc).
- ❌ Usar `import.meta.hot.accept` sin manejador en módulos que crean recursos GPU pesados (filtros, texturas) — un reload sin manejador filtra GPU memory.
- ❌ Confiar en el shape de `module.exports.default` para CJS: ahora respeta consistentemente el flag `__esModule` y el tipo del importador.
- ❌ Esperar que `vite-plugin-glsl` recompile automáticamente un `Filter` de Pixi: hace HMR del string del shader, no del objeto que lo consume.

---

## 2. PixiJS v8.18.1

PixiJS v8 reescribió el renderer (paquete único `pixi.js`, soporte WebGPU, init asíncrono, sistema de filtros nuevo, GLSL ES 3.0 por defecto). Daino usa `8.18.1`, donde varios bugs visibles de la rama 8.0–8.16 ya están resueltos (alineación de texto con `align: center/right`, `Container.cullArea` en coordenadas locales correctas, `text.width` con `wordWrap` y align no-left devuelve ancho real).

### 2.1 Inicialización asíncrona

Cambio mayor frente a v7. Constructor sin opciones, opciones en `init()`:

```js
import { Application } from 'pixi.js';

const app = new Application();
await app.init({
  resizeTo: window,
  autoDensity: true,
  resolution: window.devicePixelRatio || 1,
  background: 0x0a0a0f,
  antialias: false,           // off en juegos pixelart o con shaders propios
  preference: 'webgl',         // ver §2.6
  powerPreference: 'high-performance',
});
document.body.appendChild(app.canvas);   // OJO: ya no es app.view en v8
```

`new Application(options)` de v7 está deprecated y v8.18 logea warning si se usa con args.

### 2.2 ParticleContainer (rework total v8)

`ParticleContainer` en v8 **no acepta Sprites** como hijos. Acepta `Particle`, una estructura plana sin scene graph. Mucho más rápido pero hay que cambiar la mentalidad:

```js
import { ParticleContainer, Particle, Rectangle, Texture } from 'pixi.js';

const container = new ParticleContainer({
  boundsArea: new Rectangle(0, 0, 1920, 1080),  // OBLIGATORIO calcular bounds
  dynamicProperties: { position: true, scale: false, rotation: false, color: false },
});
const tex = Texture.from('spark.png');
for (let i = 0; i < 5000; i++) {
  container.addParticle(new Particle({ texture: tex, x: Math.random()*1920, y: Math.random()*1080 }));
}
```

Particles viven en `particleChildren` (no en `children`). Se puede mutar el array directamente para máxima velocidad. **No calcula bounds automáticamente** — hay que pasar `boundsArea` o cull manual fallará silenciosamente.

`dynamicProperties` indica qué propiedades cambian entre frames; las marcadas `false` se uploadean una sola vez al GPU. Ganancia masiva si solo se mueve `position`.

### 2.3 Text vs BitmapText vs HTMLText

| Tipo | Coste primer render | Coste cambiar string | Cuándo usar |
|---|---|---|---|
| `Text` | Alto (texture render en CPU) | **Alto** (re-render) | Texto que cambia poco (menús estáticos) |
| `BitmapText` | Coste de cargar `.fnt` o `BitmapFont.install` | **~0** (geometría de tiles pre-rendered) | HUD que cambia cada frame: score, FPS, BPM |
| `HTMLText` | DOM-based | Alto | Texto enriquecido con CSS, no para HUD de juego |

Para Daino HUD: **`BitmapText` es la opción**. Se puede generar la fuente bitmap en runtime con `BitmapFont.install({ name: 'hud', style: { fontFamily: 'Arial', fontSize: 24, fill: 0xffffff } })`. La resolución del bitmap font es independiente de `app.renderer.resolution`; si se cambia `devicePixelRatio` del canvas hay que reinstalar la fuente con la nueva `resolution` y llamar `text.onViewUpdate()`.

`SDF`/`MSDF` BitmapFonts permiten escalar sin pixelar — útiles si el HUD hace zoom.

### 2.4 Filtros custom + FFT como sampler2D

API de filtros en v8 es radicalmente distinta. Constructor toma un objeto `{ glProgram, gpuProgram, resources }`. **No hay shortcut por positional args.**

GLSL **debe ser ES 3.0** (`in`/`out` en vez de `attribute`/`varying`, `texture()` en vez de `texture2D()`, declarar `out vec4 finalColor;` en lugar de `gl_FragColor`).

#### Patrón para datos FFT vía sampler2D (no uniforms individuales)

Subir el bin FFT como una textura 1D (en realidad `Nx1` 2D) cada frame:

```js
import { Filter, GlProgram, Texture, BufferImageSource } from 'pixi.js';

const FFT_BINS = 256;
const fftBuffer = new Uint8Array(FFT_BINS);                 // de AnalyserNode
const fftSource = new BufferImageSource({
  resource: fftBuffer,
  width: FFT_BINS,
  height: 1,
  format: 'r8unorm',           // 1 byte por bin
});
const fftTexture = new Texture({ source: fftSource });

const fragment = `
in vec2 vTextureCoord;
out vec4 finalColor;
uniform sampler2D uTexture;     // input del filtro (lo da Pixi)
uniform sampler2D uFFT;          // datos FFT
uniform float uTime;

void main(void) {
  float bin = texture(uFFT, vec2(vTextureCoord.x, 0.5)).r;
  vec4 base = texture(uTexture, vTextureCoord);
  finalColor = base + vec4(bin * 0.5, 0.0, bin * 0.2, 0.0);
}
`;

const audioFilter = new Filter({
  glProgram: new GlProgram({ fragment, name: 'audio-react' }),
  resources: {
    timeUniforms: { uTime: { value: 0, type: 'f32' } },
    uFFT: fftTexture,            // textura va directa a `resources`, NO dentro de un grupo
  },
});

// cada frame:
fftBuffer.set(/* AnalyserNode.getByteFrequencyData() */);
fftSource.update();              // marca dirty
audioFilter.resources.timeUniforms.uniforms.uTime += ticker.deltaTime / 60;
```

Notas críticas:
- Los **uniforms escalares** se agrupan en sub-objetos (cada uno se compila a un UBO). El nombre del grupo (`timeUniforms`) es arbitrario.
- Las **texturas** van directamente como propiedades de `resources`, **no dentro de un grupo**. El nombre de la propiedad (`uFFT`) debe coincidir con el `uniform sampler2D` del shader.
- `uTexture` lo inyecta Pixi automáticamente como input del filtro (resultado del pase anterior). No hay que declararlo en `resources`.
- En v8 los nombres de uniforms del vertex shader cambiaron (`uOutputFrame`, `uOutputTexture`, `uInputSize`); la helper `filterVertexPosition()` ya no existe como antes — copiarla literal del ejemplo oficial `pixijs.com/8.x/examples/filters-advanced/custom`.

Para soportar WebGPU + WebGL hay que pasar **ambos** programas (`glProgram` GLSL y `gpuProgram` WGSL). Si solo se pasa uno y el renderer activo es el otro, **el filtro se omite silenciosamente** (`compatibleRenderers` calculado en bits).

### 2.5 Culling para scrolling largo

PixiJS v8 **no culla por defecto**. Para niveles largos hay dos opciones:

**Opción A — `CullerPlugin` global (mimic v7):**

```js
import { extensions, CullerPlugin } from 'pixi.js';
extensions.add(CullerPlugin);

container.cullable = true;
container.cullableChildren = true;
container.cullArea = new Rectangle(0, 0, app.screen.width, app.screen.height);
```

**Opción B — `Culler.shared.cull()` manual** en el ticker, útil para cullar solo ciertas capas (p. ej. la del nivel scrolleable, no la del HUD).

`cullArea` evita el coste de recalcular bounds globales por frame — **siempre fijar uno** en contenedores grandes con muchos hijos. Desde v8.16 `Container.cullArea` se interpreta correctamente en coordenadas locales (antes había bugs de transformación).

### 2.6 WebGPU vs WebGL2: cuál default

PixiJS auto-detecta. La doc oficial v8 recomienda dejarlo así (`preference: 'webgpu'` con fallback a WebGL). Para Daino, evidencia práctica:

- **WebGPU** gana cuando hay muchos `batch breaks` (filtros, máscaras, blend modes raros). Audio-reactive con varios filtros encadenados encaja aquí.
- **WebGL2** sigue siendo más estable y soportado universalmente. En navegadores que no exponen WebGPU (Safari < 18, Firefox sin flag) hace fallback automático.
- **Inicialización WebGPU es asíncrona** — por eso `app.init()` es `await`-able.

Recomendación pragmática: `preference: 'webgl'` por defecto en producción mientras WebGPU madura, y exponer un toggle para tests. Si se elige `'webgpu'`, los shaders del Bloque 5 necesitan **versión WGSL** además de GLSL para que el filtro funcione en ambos backends.

### 2.7 Assets.load (loader v8)

`Assets` es el sistema único de carga. `Loader.shared` y `loader.add().load()` de v7 ya no existen.

```js
import { Assets } from 'pixi.js';

await Assets.init({ basePath: '/dist/assets/', manifest: { /* opcional */ } });

// suelto:
const tex = await Assets.load('hero.png');

// bundle:
Assets.addBundle('level-1', {
  hero: 'hero.png',
  bg:   'bg-parallax.webp',
  fnt:  'hud.fnt',
});
const lvl = await Assets.loadBundle('level-1');
```

`Assets.backgroundLoad()` precarga sin bloquear. `Assets.unload()` libera la GPU memory — **importante** para niveles largos en móvil.

### 2.8 autoDensity / resolution / resizeTo

Patrón canónico para canvas full-viewport con DPR correcto:

```js
await app.init({
  resizeTo: window,                          // dispara `resize` event al cambiar el viewport
  autoDensity: true,                         // ajusta CSS size del canvas (CSS px) vs buffer (device px)
  resolution: window.devicePixelRatio || 1,  // multiplicador del backing store
  antialias: false,
});
```

- **`resolution`**: cuánto multiplica el tamaño interno del framebuffer. `window.devicePixelRatio` da nitidez en retina pero cuadruplica el coste GPU en pantallas 2x. Para juegos con muchos shaders, considerar **fijarlo en 1** y pixelizar a propósito.
- **`autoDensity: true`**: si `resolution=2`, el `<canvas>` tendrá `width=3840` pero CSS `width: 1920px`. Sin esto, el canvas se ve gigante en CSS.
- **`resizeTo: window`** llama a `app.queueResize()` automáticamente. La llamada está throttled a un frame; no hace falta debouncear.

Para `resizeTo` con elemento padre del canvas (no `window`), pasar el `HTMLElement`. Útil si el canvas vive dentro de un contenedor con sidebar.

### 2.9 Cosas que NO hacer (PixiJS v8.18)

- ❌ Usar `new Application({...})` con opciones en el constructor — ya solo `init({...})` async.
- ❌ Acceder `app.view` — es `app.canvas`.
- ❌ Meter `Sprite`s dentro de `ParticleContainer`. No acepta. Usar `Particle`.
- ❌ Olvidar `boundsArea` en `ParticleContainer` o `cullArea` en contenedores grandes — culling y filtros usan bounds y se rompen sin él.
- ❌ Escribir filtros con `attribute`/`varying`/`gl_FragColor`. Solo GLSL ES 3.0.
- ❌ Pasar texturas dentro del sub-objeto de un grupo de uniforms: van directo en `resources.uMyTex = texture`.
- ❌ Usar `Text` para HUD que cambia por frame (FPS, score, BPM). Usar `BitmapText`.
- ❌ Construir un filtro custom solo con `glProgram` y luego usarlo bajo `preference: 'webgpu'`. Quedará vacío silenciosamente.
- ❌ Confiar en `text.width === wordWrapWidth` cuando hay align center/right (cambio en v8.18 — devuelve ancho real renderizado).
- ❌ Importar `BaseTexture` — sustituido por `TextureSource` en v8.

---

## 3. Tailwind CSS v4.2.4

Tailwind v4 es una reescritura de motor (Oxide, Rust) y un nuevo paradigma de configuración: **CSS-first**, sin `tailwind.config.js` por defecto, con todo el sistema de tokens en CSS via `@theme`.

### 3.1 Qué cambia frente a v3

| Concepto | v3 | v4 |
|---|---|---|
| Config | `tailwind.config.js` (JS) | **`@theme { ... }` en CSS** |
| Importar | `@tailwind base; @tailwind components; @tailwind utilities;` | **`@import "tailwindcss";`** |
| Build engine | PostCSS plugin | **`@tailwindcss/vite`** (recomendado) o `@tailwindcss/postcss` |
| Engine | JIT en JS | **Oxide en Rust** (5x full build, 100x+ incremental) |
| Detección de contenido | Manual `content: [...]` | **Automática** |
| Container queries | Plugin `@tailwindcss/container-queries` | **Built-in** |
| Tokens en runtime | Solo en build | Expuestos como **CSS custom properties** (`--color-*`, `--spacing-*`) |
| Color space | RGB | **OKLCH** |
| `autoprefixer` / `postcss-import` | Necesarios | **Eliminados** (Tailwind los hace) |
| CLI | `tailwindcss` | **`@tailwindcss/cli`** |

### 3.2 Configuración para Daino

`vite.config.js`:
```js
import tailwindcss from '@tailwindcss/vite';
export default defineConfig({ plugins: [tailwindcss()] });
```

`src/style.css`:
```css
@import "tailwindcss";

@theme {
  --font-display: "Inter", system-ui, sans-serif;
  --color-daino-bg:   oklch(0.18 0.02 270);
  --color-daino-fg:   oklch(0.92 0.01 80);
  --color-daino-beat: oklch(0.78 0.18 30);   /* color reactivo a beats */
  --spacing-hud-gap:  0.75rem;
  --breakpoint-game:  1280px;
}

/* Las variables se exponen automáticamente como CSS custom properties:
   color: var(--color-daino-beat);  ← funciona en runtime, JS puede leerlo. */
```

PHP solo necesita inyectar la `<link>` al CSS final que produce Vite (vía manifest), no hay configuración Tailwind del lado servidor.

### 3.3 Plugins y `@plugin`

En v4 los plugins se cargan desde CSS:
```css
@plugin "@tailwindcss/forms";
@plugin "@tailwindcss/typography";
```

**No todos los plugins v3 funcionan**. Caso conocido: `@tailwindcss/forms` rompió la compilación en versiones tempranas de v4 con el plugin de Vite (issue #15816 en `tailwindlabs/tailwindcss`, reportado para v4.0.x, ya estable en 4.0.6+). En 4.2.4 funciona. Antes de adoptar un plugin v3 hay que probarlo concretamente.

### 3.4 Container queries built-in

```html
<div class="@container">
  <div class="grid grid-cols-1 @sm:grid-cols-2 @lg:grid-cols-4">…</div>
</div>
```

Variantes incluidas: `@xs` (20rem) → `@7xl` (80rem). Soporta `@max-md:`, `@min-lg:`, valores arbitrarios `@[480px]:`, named containers `@container/card` + `@sm/card:`. Ojo: los breakpoints de container son **distintos a los de viewport** (p. ej. `@md` = 28rem = 448px, mientras que `md` = 768px).

### 3.5 `@reference` para CSS modules / `<style>` aislados

Si Daino usa partials CSS importados aparte del bundle principal (p. ej. estilos para el editor de niveles aislado), **no tienen acceso a los tokens del `@theme`**. Hay que añadir:

```css
@reference "../app.css";
.my-class { @apply text-daino-beat; }
```

`@reference` importa solo las definiciones (no duplica CSS en el bundle). Mejor aún: usar `var(--color-daino-beat)` directamente y evitar `@apply`, que es más rápido en build.

### 3.6 Pluginería del Vite plugin

`@tailwindcss/vite@4.2.4`:
- Auto-watch de templates, recompila en milisegundos.
- HMR del CSS sin reload de página (inyecta el `<style>`).
- No requiere config PostCSS, no requiere `content: [...]`.
- Detecta automáticamente clases en `.html`, `.php`, `.js`, `.ts`, `.vue`, etc., dentro del root del proyecto.

Para PHP-FPM con Vite manifest: ningún cambio. El CSS sale en el manifest como un asset asociado al entry JS, y PHP lo emite como `<link rel="stylesheet">`.

### 3.7 Cambios de variantes / utilities que pellizcan

- **Buttons**: ahora `cursor: default` por defecto (matching browsers). Para volver al `cursor: pointer` previo, custom CSS en preflight.
- **Placeholders**: usan `currentColor` al 50% en vez de `gray-400`.
- **Color palette**: refrescada y en OKLCH. Hay diferencias visuales sutiles vs v3 incluso con los mismos nombres.
- **Compatibilidad con Sass/Less/Stylus**: explícitamente **no soportada**. Tailwind v4 espera ser el preprocesador.
- **Plugin `tailwindcss-animate`** y similares: revisar compatibilidad antes de instalar; muchos no han migrado a la nueva API de plugins.
- Variantes deprecated/renamed: `decoration-slice`/`decoration-clone` → `box-decoration-slice`/-clone; varias utilidades de opacidad pasan de `text-opacity-*` a `text-{color}/{opacity}`.

### 3.8 Cosas que NO hacer (Tailwind v4.2.4)

- ❌ Crear `tailwind.config.js` por costumbre — no se usa por defecto. Si se crea para compatibilidad, hay que `@config "./tailwind.config.js";` explícito en el CSS.
- ❌ Usar `@tailwind base; @tailwind components; @tailwind utilities;` — sustituido por `@import "tailwindcss";`. No hay error claro si se mezcla, pero genera CSS duplicado.
- ❌ Mantener `postcss-import` y `autoprefixer` en `postcss.config`. v4 los hace internamente; tenerlos doble explota o duplica reglas.
- ❌ Usar el plugin **PostCSS** (`@tailwindcss/postcss`) cuando ya estamos en Vite — el plugin Vite es más rápido y tiene mejor HMR. Solo elegir PostCSS si el bundler no es Vite.
- ❌ Aplicar `@apply` en un `<style>` Vue/Svelte aislado o en CSS modules sin `@reference` — los tokens de `@theme` no llegan.
- ❌ Usar el plugin `@tailwindcss/container-queries` — eliminado, está built-in.
- ❌ Asumir Node 18: el upgrade tool requiere **Node 20+**. v4 al ejecutarse exige Node ≥ 18 pero el motor Oxide gana con runtimes recientes.
- ❌ Esperar que Tailwind soporte Safari 16.3 o Chrome 110: v4 requiere **Safari 16.4+, Chrome 111+, Firefox 128+** (mismo target que el default de Vite 8 — no es coincidencia).

---

## 4. Bun 1.2.3

Bun es el runtime/toolchain del proyecto. La versión instalada es `1.2.3` (rama 1.2). Bun.SQL nació en la rama 1.2 con solo Postgres y se amplió a MySQL/MariaDB/SQLite en versiones posteriores; Bun 1.3 lo unifica.

### 4.1 Bun.SQL en 1.2.x

| Versión | SQL adapters disponibles |
|---|---|
| **1.2.0** (ene 2025) | PostgreSQL nativo (introducido como `Bun.sql`) + SQLite (vía `bun:sqlite`, separado) |
| 1.2.21 (ago 2025) | MySQL/MariaDB añadidos a `Bun.SQL`; SQLite también vía la misma API tagged-template |
| 1.3 (oct 2025) | API unificada estable, error classes exportadas (`Bun.SQL.PostgresError`, `Bun.SQL.MySQLError`, `Bun.SQL.SQLiteError`) |

**En 1.2.3 concretamente** solo hay cliente Postgres nativo (`Bun.sql`). MySQL/MariaDB nativo **no llega hasta 1.2.21**. Si Daino necesita conectar a MariaDB desde Bun directamente, **hay que upgrade a ≥ 1.2.21 o, mejor, a 1.3.x**.

API tagged-template (cuando se tiene 1.2.21+):
```ts
import { sql, SQL } from "bun";
const users = await sql`SELECT * FROM users WHERE active = ${true}`;
const mariadb = new SQL("mysql://user:pwd@127.0.0.1:3306/daino");
```

Restricciones (válidas también en 1.3):
- Llamar `sql(...)` como función plana (no tagged template) lanza error desde 1.3 (cambio de comportamiento).
- MySQL no soporta `RETURNING`; usar `result.lastInsertRowid` o un `SELECT` posterior.
- MySQL no tiene tipos array nativos como Postgres.
- `LOAD DATA INFILE` no implementado.

Para Daino el caso de uso típico (PHP-FPM hace SQL al backend) es que Bun **no toque la DB en producción** — es solo el toolchain del frontend. La utilidad de Bun.SQL aquí sería para scripts de migración local, seeders o tooling auxiliar.

### 4.2 Lockfile: binario vs texto

| Versión | Lockfile default | Comportamiento |
|---|---|---|
| ≤ 1.1.38 | `bun.lockb` (binario) | No se puede revisar en PR, conflictos imposibles a mano |
| 1.1.39 | Binario por defecto, **`bun.lock` opt-in** vía `--save-text-lockfile` | Coexistencia |
| **1.2.0+** | **`bun.lock` (texto, JSONC) por defecto** | GitHub renderiza diffs, VSCode highlight, Dependabot puede leerlo |

Bun 1.2.3 usa `bun.lock` (texto) por defecto. Si el repo viene de Bun 1.1 todavía con `bun.lockb`, migrar:
```bash
bun install --save-text-lockfile --frozen-lockfile --lockfile-only
rm bun.lockb
```

`bun.lock` es JSONC, soporta comentarios. `bun install` cacheado con lockfile texto es ~30% más rápido que con binario en 1.1.38 (medición oficial).

### 4.3 Compatibilidad Bun + Vite

Bun ejecutando Vite (`bun run vite` o `bun --bun run vite`) funciona en general. Diferencias relevantes:

- En **Linux/macOS** suele funcionar sin sobresaltos para Vite 6/7/8.
- En **Windows** hay tickets históricos sobre paths, watch de FS y polyfills de Node. Bun en Windows mejoró mucho desde 1.1.x; aun así, si el equipo mezcla Windows + WSL, **WSL es la opción segura**.
- Vite puede correrse con Node y con Bun como package manager (recomendado: usar Bun solo como package manager + script runner; dejar que Vite use su propio Node embebido si hay rarezas). El comando estándar `bunx vite` funciona.
- Bun 1.3 introduce su propio dev server con HMR built-in, pero **no estamos cambiando de Vite a Bun-native** (eso lo decide doc 04/05). Esto es solo nota informativa.

### 4.4 Camino a Bun 1.3+ (qué mejora)

Si Daino actualiza más tarde:
- **Bun.SQL unificado** (Postgres/MySQL/MariaDB/SQLite con misma API).
- **Redis client built-in**.
- **Frontend dev server con HMR + React Fast Refresh** built-in (alternativa a Vite — irrelevante para Daino mientras mantengamos Vite).
- **Isolated installs** opcionales (más estricto que pnpm sobre acceso a deps no declaradas).
- **`bun build --compile --target=browser`** para HTML self-contained.
- ~10–30% menos memoria en frameworks comunes.

Breaking en 1.3 a tener presente al actualizar:
- TypeScript types reorganizados (`@types/bun` auto-detecta DOM vs Node).
- `Bun.serve()` types reescritos (afecta WebSocket data).
- `module: "Preserve"` por defecto en TS.
- `sql(...)` no-tagged-template lanza error.

`bun upgrade` ejecuta el update in-place. Probar siempre el frontend completo después: HMR, build, manifest, output.

### 4.5 Cosas que NO hacer (Bun 1.2.3)

- ❌ Esperar Bun.SQL para MySQL en 1.2.3. Llegó en **1.2.21**. Si es requisito, planificar upgrade.
- ❌ Commitear `bun.lockb` y `bun.lock` a la vez. Solo el de texto en 1.2+.
- ❌ Asumir paridad Windows nativa. Si el equipo tiene devs Windows, validar el flujo entero (incluyendo `vite-plugin-glsl` watch) o pasar a WSL.
- ❌ Mezclar `bunx vite` con `npm run dev` en el mismo repo en momentos distintos: cada uno puede actualizar el lockfile a su manera. Estandarizar.
- ❌ Usar `Bun.sql` sin pre-conexión cuando la primera query es crítica en latencia. Existe `--sql-preconnect` (1.3+) y para 1.2 hay que abrir conexión manual al boot.
- ❌ Tratar Bun como drop-in 100% de Node sin testear. Los reportes de segfault en módulos nativos siguen siendo no triviales aunque cada vez menos comunes.

---

## Apéndice — Resumen de versiones mínimas y targets

| Item | Versión instalada | Mínimo recomendado | Browser target efectivo |
|---|---|---|---|
| Vite | 8.0.10 | 8.0.x | Chrome 111, Edge 111, FF 114, Safari 16.4 |
| Tailwind | 4.2.4 | 4.0.6+ | Chrome 111, FF 128, Safari 16.4 |
| @tailwindcss/vite | 4.2.4 | = Tailwind core | — |
| PixiJS | 8.18.1 | 8.16+ (fixes culling/text) | WebGL2 + WebGPU opcional |
| vite-plugin-glsl | (a verificar) | ≥ 1.1.2 (moduleGraph fix), ideal 1.5+ | — |
| Bun | 1.2.3 | 1.2.0 (lockfile texto) | — |

Las tres tecnologías frontend (Vite 8, Tailwind 4, PixiJS 8 con WebGPU opcional) coinciden en exigir navegadores **publicados de finales de 2022 en adelante**. Si Daino tiene que soportar dispositivos más viejos, las decisiones (target build, fallback WebGL, polyfill) se toman en docs **04/05/06/07**.
