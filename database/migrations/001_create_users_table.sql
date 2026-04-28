-- Tabla users — registro local mínimo cuyo único propósito es enlazar con
-- Vout (vout_id, UUID v4) y servir de FK a las tablas de dominio.
--
-- Sin columna de password: Daino NO gestiona credenciales locales (invariante
-- CLAUDE.md "No local auth"). Datos mostrables (display name, email, avatar)
-- viajan en los claims del JWT validado; aquí solo cacheamos `username` y
-- `avatar_url` para rankings y para no llamar a /api/me en cada paint.
--
-- La fila se crea lazily en el primer callback OAuth exitoso (upsert por
-- vout_id), no por un formulario de registro.

CREATE TABLE users (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vout_id         CHAR(36) NOT NULL,
    username        VARCHAR(50) NOT NULL,
    avatar_url      VARCHAR(500) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at   TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_vout_id (vout_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
