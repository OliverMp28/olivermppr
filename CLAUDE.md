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
4. **`docs/vout-integration/integration-guide.md`** — the real Vout contract (overrides any earlier OAuth description in docs 01/03).
5. **`docs/refactorizacion/02-Informe-Evolucion-Tecnica-DAINO.md`** — most-current technical doc.
6. **`docs/refactorizacion/03-Plan-Desarrollo-Vout-Dino.md`** — phased plan; Phases 1 and 4 are Vout-side (already built, not applicable). Only Phases 2 and 3 apply to this repo.
7. **`docs/refactorizacion/01-Estrategia-Integracion-Vout-Dino.md`** — historical context.

The `.sql` legacy file is reference-only — do not import it into the new schema without asking. The new schema (see doc 04 §5) is deliberately different.

## Current state

- No `composer.json`, `package.json`, `docker-compose.yml`, or source files exist yet.
- There is **nothing to build, lint, or test** until scaffolding starts.
- If the user asks for commands to run (`npm run dev`, `composer install`, etc.), they will only work **after** the scaffolding phase completes.

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
