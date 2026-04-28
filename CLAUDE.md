# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

This directory **will become the new Daino codebase** (rewrite of a legacy PHP/JS/p5.js side-scroller). Right now it contains only planning/reference documents in `docs/`. Code will be scaffolded here based on those documents.

- **Daino** is a side-scroller where each level is a song. Music duration defines the level length and the BPM/intensity drive obstacle generation and a fullscreen audio-reactive background (the page background, not the game container). Inspirations: Piano Tiles, Geometry Dash.
- **Vout** is a separate, already-built portal that acts as OAuth2 Identity Provider for this ecosystem. Daino will consume it as an OAuth2 client — no local passwords in Daino. Vout's code lives in another repo; we never touch it here.
- **Same remote repo for legacy and v2.** The new Daino shares the remote `https://github.com/OliverMp28/daino.git` with the legacy version. They are separated **by branches**, not by repos (see Branch strategy below).
- **Legacy Daino working copy** is already cloned at `../daino-legacy/` (sibling to this folder, i.e. `c:/Users/34642/Documents/Proyectos Web/daino-legacy/`). Read-only reference — do not modify it, do not import code mechanically.

## Where everything lives

```
docs/
├── refactorizacion/         ← planning documents for the rewrite
│   ├── 00-LEEME-PRIMERO.md  ← read this first for order of authority
│   ├── 01-...               ← historical strategy doc
│   ├── 02-...               ← most-current technical plan (PHP 8.5.3, PixiJS)
│   ├── 03-...               ← phased roadmap (Phases 2+3 apply to Daino)
│   ├── 04-Decisiones-Finales.md        ← AUTHORITATIVE when docs conflict
│   ├── 05-Estructura-Proyecto.md       ← AUTHORITATIVE folder layout
│   ├── 06-UX-Interfaz-Gaming.md        ← AUTHORITATIVE UX direction (game-like, not web-like)
│   └── pdfs/                ← original PDFs (redundant with the .md)
├── vout-integration/
│   └── integration-guide.md ← OAuth2/PKCE contract of the real Vout IdP
└── legacy/
    └── dinohtml-legacy.sql  ← dump of the old Daino DB, for reference only
```

## Private docs (gitignored)

Some planning docs contain internal Vout ecosystem decisions or legacy PII and are **gitignored** — they exist locally on the project owner's machine but are not published. If you cloned this repo and don't see these files, that's expected:

- `docs/legacy/dinohtml-legacy.sql` (legacy DB dump with emails + bcrypt hashes)
- `docs/refactorizacion/pdfs/` (redundant PDFs of the same content)
- `docs/refactorizacion/01-Estrategia-Integracion-Vout-Dino.md` (Vout architectural reasoning)
- `docs/refactorizacion/03-Plan-Desarrollo-Vout-Dino.md` (Vout implementation plan)
- `docs/refactorizacion/04-Decisiones-Finales.md` (references Vout internals)

When Claude Code runs locally on the project owner's machine, all these files ARE available and should be read as authoritative per the hierarchy below.

## Authority hierarchy when docs disagree

When two docs contradict, follow this order (highest wins):

1. **`docs/refactorizacion/04-Decisiones-Finales.md`** — explicit reconciliation.
2. **`docs/refactorizacion/05-Estructura-Proyecto.md`** — the agreed folder layout.
3. **`docs/refactorizacion/06-UX-Interfaz-Gaming.md`** — agreed UX direction (game-like, no navbar/footer, full-viewport shader background).
4. **`docs/refactorizacion/07-Hallazgos-Investigacion-Tecnica.md`** — refines docs 04/05/06 with research findings (April 2026). Contains critical gotchas: PHP 8.5 magic-method deprecations, Alpine `intl` slow build, `BitmapText` vs `Text` for HUD, JWKS caching, etc.
5. **`docs/vout-integration/integration-guide.md`** — the real Vout contract (overrides any earlier OAuth description in docs 01/03).
6. **`docs/refactorizacion/09-Stack-Frontend-Cheatsheet.md`** — pinned-version reference for Vite 8 / Tailwind v4 / PixiJS v8 / Bun 1.2 (gotchas, NO-hacer lists, code patterns). **Reference only — explicitly does NOT override 04/05/06/07.** When implementing a frontend file, read this first to avoid the documented foot-guns (HMR de shaders, FFT como sampler2D, BitmapText vs Text, etc.).
7. **`docs/refactorizacion/08-Bloques-Operativos.md`** — operational roadmap. Defines what each "Bloque N" delivers, in what order, with what dependencies. **Supersedes the phase numbering of doc 03 for this repo.** When the user says "Bloque 3", the source of truth is here.
8. **`docs/refactorizacion/02-Informe-Evolucion-Tecnica-DAINO.md`** — most-current technical doc.
9. **`docs/refactorizacion/03-Plan-Desarrollo-Vout-Dino.md`** — phased plan; Phases 1 and 4 are Vout-side (already built, not applicable). Phases 2 and 3 are subsumed by doc 08's Bloques 1–7.
10. **`docs/refactorizacion/01-Estrategia-Integracion-Vout-Dino.md`** — historical context.

The `.sql` legacy file is reference-only — do not import it into the new schema without asking. The new schema (see doc 04 §5) is deliberately different.

## Current state

**Bloque 0 (scaffolding base) — completo.** Existen y funcionan:
- `composer.json` + `vendor/` (lcobucci/jwt 5.x, vlucas/phpdotenv 5.x, league/oauth2-client 2.x).
- `package.json` + `bun.lock` + `node_modules/` (Vite 8.0.10, PixiJS 8.18.1, Tailwind v4.2.4 + `@tailwindcss/vite` 4.2.4). El upgrade Vite 6 → 8 ya se hizo y `bun run build` produce manifest válido en ~250ms; la gotcha histórica de doc 07 §A.4 está superada en esta combinación de versiones.
- `docker-compose.yml` con servicios `nginx` (1.27-alpine), `php` (8.5.3-fpm-alpine, build local), `mariadb` (11.4). Healthcheck en mariadb.
- `docker/php/Dockerfile`, `docker/nginx/default.conf` (deny a `app/`, `vendor/`, `.env`, etc.; docroot `/public`).
- `.env`, `.env.example`, `.editorconfig`, `.dockerignore`, `.gitignore`.
- `vite.config.js`: dev server en `:5173`, build a `public/build/` con manifest.

**Bloque 1 (mini-framework MVC) — completo.** Existen y funcionan:
- `app/bootstrap.php` — autoload, dotenv, error_reporting, timezone UTC, exception handler global (PDO→503, resto→500), `Session::start()`.
- `app/Core/Env.php` — accesor tipado `string/int/bool/require`. Lanza si falta y no hay default.
- `app/Core/Request.php` — `capture()`, `method/path/query/input/header/bearerToken/isJson/ip/rawBody`. Method spoofing `_method` sobre POST. Parseo automático de body JSON.
- `app/Core/Response.php` — `json/html/redirect/noContent/header/send`. JSON con `JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`. `send()` no llama `exit`.
- `app/Core/Session.php` — driver archivos en `storage/sessions/`, cookie `DAINO_SESSID; HttpOnly; SameSite=Lax; Secure` (en prod), helpers CSRF (`csrfToken/validateCsrf` con `hash_equals`).
- `app/Core/Database.php` — singleton PDO contra MariaDB. `utf8mb4`, exceptions, fetch assoc, sin emulated prepares, sin persistente.
- `app/Core/Router.php` + `app/Core/RouteEntry.php` — instancia (no estático), sintaxis `{id}`, grupos `prefix`+`middleware`, middleware encadenable por ruta. Resolver `'Controller@method'` a `App\Controllers\Controller`. 404/405 plain text con `Allow:`.
- `app/Core/ViteAssets.php` — puente Vite↔PHP (modo dev y manifest de build).
- `routes/web.php` — `GET /` smoke real.
- `routes/api.php` — `GET /api/health` smoke real.
- `public/index.php` — front controller: bootstrap → Router → require routes → dispatch → send.
- `storage/sessions/`, `storage/logs/`, `storage/cache/` (los dos últimos gitignored vacíos).

**Decisiones aprobadas durante el Bloque 1** (vinculantes para bloques siguientes):
- Sintaxis de parámetros de ruta: `{id}` (no `:id`).
- Middlewares: por grupo y por ruta. No globales.
- Method spoofing `_method` activo (POST → PUT/PATCH/DELETE).
- Session driver: archivos. Cookie `DAINO_SESSID`. CSRF: token único por sesión, regenerado en login/logout.
- Env: lanza si falta y no hay default; booleanos flexibles (`true|1|yes|on`); lee `$_ENV` cada vez (sin caché).
- DB: `utf8mb4`, no persistente, prepared statements reales.
- 503 controlada para PDOException vía `set_exception_handler` global.
- `Response::send()` NO hace `exit`.
- View.php aplazado al Bloque 2 (cuando aparezcan templates).

**Bloque 2 (persistencia) — completo.** Existen y funcionan:
- `database/migrations/001..005_*.sql` — 5 tablas (`users`, `levels`, `progress`, `rankings`, `comments`) con FKs en cascada, CHECK constraints (`difficulty 1..5`, `percentage 0..100`, `is_public ⇒ file_path != NULL`), charset `utf8mb4_unicode_ci`, engine InnoDB.
- `database/seeds/` (vacío, no se sembraron datos demo — primer login real vía Vout creará el primer usuario).
- `scripts/migrate.php` — runner idempotente con `composer migrate` y `composer migrate -- status`. Tabla de control `_migrations`.
- `app/Models/User.php`, `Level.php`, `Progress.php`, `Ranking.php`, `Comment.php` — finders estáticos (`findById`, `findBy*`) que devuelven `?Model` o `null`; mutación por instancia (`->update`, `->setPublic`, `->setVisibility`, `->delete`); `Progress::upsert` atómico que solo sube puntos (no degrada); `Ranking::recompute` agrega desde `progress`.

**Decisiones aprobadas durante el Bloque 2:**
- Modelos sin ORM: PDO directo, propiedades tipadas, `readonly` para id + claves inmutables, `fromRow()` privado para hidratación.
- `null` en finders cuando no hay match (no throw). Throw solo en errores invariantes (e.g. lastInsertId no recuperable).
- Schema en inglés y snake_case; ningún rastro del legacy `register`/`contraseña`.
- Charset `utf8mb4 / utf8mb4_unicode_ci` (no `_0900_ai_ci` que es MySQL 8, no MariaDB).
- Sin rollback explícito en el runner; en dev se dropea+re-runea, en prod se escribe migración nueva.

**Bloques 3–8 — pendientes.** La hoja de ruta operativa vive en `docs/refactorizacion/08-Bloques-Operativos.md`. Resumen: 3 = identidad Vout (OAuth2 + JWT + iframe bridge), 4 = frontend foundation (PixiJS + audio + shader), 5 = game core, 6 = UI/HUD/API client, 7 = endpoints API, 8 = hardening.

**Cómo arrancar y verificar:**
```bash
docker compose up -d
# La app responde en http://localhost:${APP_PORT:-8090} (ver .env).
# IMPORTANTE: si Windows tiene IIS activo en :80/:8080, choca; usa APP_PORT distinto.
curl.exe -s http://localhost:8090/api/health
# → {"status":"ok","php":"8.5.3","time":"..."}
```

## Architectural invariants (load-bearing — do not contradict)

These decisions are fixed. If any doc seems to contradict them, doc 04 is correct:

- **Daino is a stateless Resource Server; Vout is the IdP.** Daino never queries Vout's DB. It validates RS256 JWTs locally via Vout's JWKS endpoint (`/oauth/token/keys`), with a local cache of the public keys.
- **OAuth flow:** Authorization Code **with PKCE** (`S256`). `state` is mandatory.
- **JWT library:** `lcobucci/jwt` (chosen for explicitness over `firebase/php-jwt`). Do not substitute.
- **No local auth:** no user registration, no password reset, no "Login with Google" in Daino. All identity comes from Vout. The only stored identifier is `vout_id` (CHAR(36) UUID).
- **Stack:** **PHP 8.5.3 pinned** (`php:8.5.3-fpm-alpine` — must match Vout's exact version) + PDO (MariaDB) + `lcobucci/jwt` + `vlucas/phpdotenv` + `league/oauth2-client`. Frontend: Vanilla JS (ES Modules) + **PixiJS v8** + Web Audio API (+ Meyda if BPM detection needs it) + Tailwind v4 via Vite. **No p5.js.** **No Laravel.**
- **UX is game-like, not web-like.** No navbar, no footer, no hamburger menu. Single `<canvas>` at `100vw × 100vh`. Audio-reactive shader is always the deepest layer, always full-viewport. Menus are modal overlays / slide-in panels, not separate pages. See doc 06.
- **postMessage handshake (only when Daino is embedded in Vout):** iframe sends `READY`, parent replies with `AUTH_TOKEN`. Tokens never travel in URL params. Origin validation is mandatory — never `"*"`.
- **MP3 handling is Local-First.** `FileReader` + `AudioContext.decodeAudioData`. Uploads to the server are optional and only on explicit "save level" actions.
- **MediaPipe is Vout's responsibility.** Daino only receives `GAME_ACTION` messages via postMessage. We don't import `@mediapipe/*` in Daino.

## Critical gotchas (from research, April 2026)

These are load-bearing details discovered in `docs/refactorizacion/07-Hallazgos-Investigacion-Tecnica.md`. Don't repeat the mistakes:

- **HUD text in canvas: use `BitmapText`, never `PIXI.Text`.** `PIXI.Text` regenerates a texture per character change — kills GPU on a 60fps score counter. For DOM HUD overlays, this rule doesn't apply.
- **PixiJS v8 `Application.init()` is async** (breaking from v7). Always `await app.init({...})` before adding stages.
- **Pass FFT data to GLSL shaders as a `sampler2D` texture, not as 1024 individual uniform floats.** Saturates the uniform bus.
- **PHP 8.5: `__sleep` and `__wakeup` are deprecated** in favor of `__serialize` / `__unserialize`. Critical because PKCE `code_verifier` and `state` live in `$_SESSION` — if we ever ship a custom session handler, use the new methods or PKCE breaks mid-flow silently.
- **PKCE `code_verifier` lives ONLY in `$_SESSION`** — never cookies, never `localStorage`. Same for `state`.
- **JWKS keys must be cached (PSR-16)** with ~1h TTL. Force refresh on unknown `kid` (key rotation). Always validate `iss`, `aud`, `exp` with **60s leeway** for clock drift.
- **Alpine + PHP 8.5 + `intl`: 30 minute build the first time** due to musl missing pre-compiled libs. Use `mlocati/docker-php-extension-installer` or accept the one-time cost.
- **WSL2 + code in `/mnt/c/...` destroys HMR performance.** If on Windows and HMR feels slow, move project into the WSL2 native partition.
- **`AudioContext.onstatechange`** must be handled — fullscreen entry/exit, tab switch, or power-save can suspend audio. Pause the game, don't let the player desync.
- ~~**Tailwind v4 + Vite 7+** had peer-dep issues historically; verify before upgrading from Vite 6.~~ **Resolved as of April 2026**: this project runs Vite 8.0.10 + `@tailwindcss/vite` 4.2.4 + tailwindcss 4.2.4 cleanly (`bun run build` produces a valid manifest, no peer-dep warnings). Leave the entry here as a tombstone — if a future bump regresses, this is the line to revisit.
- **AnalyserNode parameters**: `fftSize: 2048`, `smoothingTimeConstant: 0.82`, `minDecibels: -100`, `maxDecibels: -30`. Don't reinvent these.

## Naming conventions

- Project spelling: `Vout` (capital V), `Daino` or `DAINO`.
- Linkage column: `vout_id` (snake_case in the new schema — different from the older doc 01/03 which used `Vout_id`; doc 04 fixes the snake_case).
- Old user table `register` (legacy) is **not** carried over. The new table is `users`.

## When the user asks to scaffold code

- Before creating `composer.json`, `package.json`, `docker-compose.yml`, or any source file: re-read `docs/refactorizacion/04-Decisiones-Finales.md` and `05-Estructura-Proyecto.md`. They are the source of truth.
- Never write to `docs/refactorizacion/pdfs/` — those are archives.
- Never modify files under `docs/vout-integration/` — that is the external contract published by the Vout team.
- `docs/legacy/` is read-only reference.

## Branch strategy (same remote, separate branches)

The remote `https://github.com/OliverMp28/daino.git` hosts both the legacy and the v2 rewrite. They are separated by branches:

- **`main`**: currently the legacy codebase. Leave untouched until the v2 is ready to promote.
- **`ramita`**: a secondary branch that exists on the remote (legacy work-in-progress). Ignore unless the user asks about it.
- **`legacy`** (to be created when user approves): a permanent named branch pointing at the current `main` HEAD — preserves the old code forever under an obvious name.
- **`rewrite/v2`** (to be created): orphan branch where the new Daino is developed. Orphan = no common ancestor with `main`, so the legacy files don't appear as "deleted" in the first v2 commit.

The `daino/` folder itself is **not yet a git repo** (`ls -la` shows no `.git`). When the user approves the branch plan, initialize it with:

```
cd daino/
git init
git remote add origin https://github.com/OliverMp28/daino.git
git fetch origin
git checkout --orphan rewrite/v2
git add .
git commit -m "..."
git push -u origin rewrite/v2
```

Never force-push `main`. Never rewrite legacy commits.

## When the user asks to read the legacy codebase

Already cloned at `../daino-legacy/` (sibling to this folder). Treat it as read-only. To look at something, open it in a second editor window or inspect via Bash/Grep with the absolute path. Do not copy-paste legacy code into the rewrite without first re-designing it — the point is to improve, not migrate.
