# Informe de Evolución Técnica: Proyecto DAINO

Este documento establece las directrices para la refactorización y mejora del proyecto **Daino**. Se centra en el rendimiento, la escalabilidad de niveles y la experiencia visual inmersiva, utilizando un stack moderno basado en **Vite, PHP 8.5 y PixiJS**.

---

## 1. Motor de Juego y Gráficos: Migración a PixiJS

Para superar las limitaciones del renderizado basado en DOM o Canvas 2D simple, se ha decidido utilizar **PixiJS** como motor principal.

### Razones de la elección

- **Aceleración por Hardware (WebGL/WebGPU):** Permite renderizar miles de objetos simultáneamente sin "lag", algo vital para los efectos visuales de fondo.
- **Gestión de Sprites:** Sustituiremos la intercalación manual de imágenes por **SpriteSheets**. Esto reduce las peticiones HTTP y mejora la fluidez de las animaciones.
- **Contenedores y Filtros:** PixiJS facilita la separación de capas (Fondo reactivo vs. Juego activo) y permite aplicar filtros de post-procesado (blur, glow, chromatic aberration) de forma eficiente.

### Implementación de Físicas

- **Custom AABB Physics:** Dado que el juego es un side-scroller de obstáculos, no utilizaremos un motor de físicas pesado como Matter.js. Implementaremos una lógica de colisiones propia (AABB) optimizada para detectar colisiones entre el Dino y los obstáculos en cada frame del ticker de PixiJS.
- **Gravedad Dinámica:** La fuerza del salto podrá ser influenciada por la intensidad de la música en tiempo real.

---

## 2. Audio-Reactividad y Efectos Visuales

Sustituiremos la dependencia de p5.js por una integración directa con la **Web Audio API** integrada en PixiJS o mediante una utilidad ligera.

### Estrategia de Visualización

1. **Analizador de Frecuencias:** Utilizaremos `AudioContext.createAnalyser()` para obtener datos de la música en tiempo real (Fast Fourier Transform - FFT).
2. **Shaders (GLSL):** Los efectos de fondo "chulos" se ejecutarán mediante **fragment shaders**. Esto permite que el fondo se mueva, cambie de color o pulse al ritmo de los bajos sin consumir CPU, ya que todo el cálculo se delega a la GPU.
3. **Partículas:** Implementaremos un sistema de partículas en PixiJS donde la velocidad y cantidad de partículas emitidas dependan del volumen (ganancia) de la canción actual.

---

## 3. Gestión de Niveles y Escalabilidad (MP3/User Content)

El sistema debe ser **"Local-First"** para la carga de archivos, pero con capacidad de persistencia.

### Flujo de Usuario y Carga

- **Subida Local:** El usuario podrá arrastrar un `.mp3`. Mediante la API `FileReader` de JS, el juego procesará el archivo sin necesidad de subirlo inicialmente al servidor PHP, permitiendo juego instantáneo.
- **Generación Procedural:** El nivel no se diseña a mano. Se analizará la duración del MP3 y sus picos de intensidad para colocar obstáculos.
  - **Algoritmo de Mapeo:** Canción rápida (BPM alto) = Mayor velocidad del scroll + Obstáculos más frecuentes.
- **Almacenamiento en MariaDB:** Si el usuario decide "Guardar Nivel", el PHP recibirá la metadata (nombre, duración, configuración del generador) y, opcionalmente, el archivo se almacenará en un sistema de archivos persistente vinculado a su ID de usuario.

---

## 4. Guía para Claude Code (Instrucciones de Desarrollo)

Para el desarrollo con Claude Code, se recomiendan las siguientes herramientas y enfoques:

### Herramientas MCP y Skills Recomendados

- **Filesystem & Shell:** Claude Code debe tener acceso total para gestionar la estructura de carpetas de Vite y ejecutar los contenedores de Docker.
- **Skill de Análisis de Audio:** Se sugiere que Claude busque patrones en librerías como **meyda** para la extracción de características de audio si la implementación nativa de Web Audio se queda corta.
- **Generación de Assets con IA:** Para los sprites de los niveles (nuevos dinosaurios o enemigos), Claude puede integrarse con herramientas de generación de imágenes tipo "Pixel Art" para expandir el catálogo visual dinámicamente.

### Configuración del Entorno (Docker)

- **PHP 8.5:** Configurar el `Dockerfile` con extensiones para manejo de archivos y JWT (para la comunicación con Vout).
- **Vite Dev Server:** Exponer el puerto `5173` para el hot-reload del frontend en Vanilla JS.
- **Estructura de Carpetas:**
  - `/src`: Lógica de PixiJS y Web Audio.
  - `/public/assets`: Spritesheets y efectos base.
  - `/api`: Endpoints PHP para persistencia de niveles y rankings.

---

## 5. Resumen de Decisiones Clave

| Punto     | Decisión                          | Beneficio                                                    |
| --------- | --------------------------------- | ------------------------------------------------------------ |
| Gráficos  | PixiJS (Aceleración WebGL)        | Rendimiento fluido a 60fps constantes.                       |
| Visuales  | Web Audio API + GLSL Shaders      | Efectos envolventes sin lag de CPU.                          |
| Niveles   | Generación Procedural por MP3     | Escalabilidad infinita; cada canción es un mapa único.       |
| Entorno   | Docker (PHP 8.5) + Vite           | Flujo de desarrollo moderno y portable.                      |
| Contenido | Local-First / User Upload         | Privacidad y rapidez; ahorro de ancho de banda en servidor.  |

---

> *Este informe debe ser procesado por el agente de desarrollo para iniciar la fase de scaffolding del proyecto Daino.*
