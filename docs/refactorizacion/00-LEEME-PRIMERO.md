# LEEME PRIMERO — Orden de lectura de los documentos

Esta carpeta contiene la planificación completa del **nuevo Daino**. Los documentos no deben leerse por su número de orden (son históricos), sino por su **vigencia**.

> ⚠️ **Algunos docs de esta carpeta están en `.gitignore`** (ver raíz del repo) y solo existen localmente. Si clonaste este repo y algunos archivos no aparecen, pídelos al dueño del proyecto. Los docs privados son: `01-Estrategia-Integracion-Vout-Dino.md`, `03-Plan-Desarrollo-Vout-Dino.md`, `04-Decisiones-Finales.md` y `pdfs/`. Contienen decisiones internas del ecosistema Vout que no se publican.

## Orden de lectura recomendado

| # | Documento | Estado | Propósito |
|---|-----------|--------|-----------|
| 1 | `04-Decisiones-Finales.md` | ✅ **Autoridad** | Decisiones ganadoras donde los docs 02 y 03 se contradicen. **Leer siempre primero.** |
| 2 | `05-Estructura-Proyecto.md` | ✅ **Autoridad** | Estructura de carpetas y stack definitivo del nuevo Daino. |
| 3 | `06-UX-Interfaz-Gaming.md` | ✅ **Autoridad** | Dirección UX: Daino como videojuego web (sin navbar/footer, shader a 100% viewport, HUD discreto). Sobrescribe cualquier layout "web clásica" implícito en docs anteriores. |
| 4 | `07-Hallazgos-Investigacion-Tecnica.md` | ✅ **Autoridad** | Hallazgos curados de la investigación de abril 2026 (Gemini Deep Research). Refina y matiza decisiones de docs 04/05/06. Contiene gotchas críticas (PHP 8.5 magic methods, Alpine + intl, BitmapText, JWKS, etc.). |
| 5 | `02-Informe-Evolucion-Tecnica-DAINO.md` | 🟢 Vigente | Detalles técnicos del juego (PixiJS, Web Audio API, shaders, generación procedural). |
| 6 | `03-Plan-Desarrollo-Vout-Dino.md` | 🟡 Parcialmente vigente | Plan por fases. **Las fases 1 y 4 son de Vout (ya hecho). Solo son vigentes las fases 2 y 3 (Dino).** Cuando contradiga a doc 02, gana doc 02. |
| 7 | `01-Estrategia-Integracion-Vout-Dino.md` | 🟡 Contexto | Razón arquitectónica de por qué Vout es IdP. **Sustituido por `../vout-integration/integration-guide.md` en cuanto a cómo integrar.** |

## Fuentes externas relevantes

- `../vout-integration/integration-guide.md` — Guía oficial de integración OAuth2 + PKCE con Vout. **Esta es la fuente de verdad** para la parte de autenticación. Los docs 01/03 describen el diseño previsto; este documento describe el contrato real del Vout ya construido.
- `../legacy/dinohtml-legacy.sql` — Dump del Daino viejo. Útil para ver qué tablas existían (`register`, `canciones`, `progreso`, `comentarios`, `ranking`) y decidir qué se conserva / rediseña.

## ¿Por qué la numeración no refleja la vigencia?

Los tres primeros documentos (01, 02, 03) se generaron en orden cronológico durante distintas iteraciones del diseño. El 02 quedó como el más actualizado técnicamente (PHP 8.5, PixiJS) pero el 03 se redactó antes. El doc 04 (este añadido) existe para fijar las decisiones finales sin reescribir todo lo anterior.
