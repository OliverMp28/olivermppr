-- Tabla comments — comentarios (per-level y globales con level_id NULL).
--
-- is_visible mantiene la lógica de moderación blanda del legacy (un mod podría
-- ocultar un comentario sin borrarlo). body con VARCHAR(400) es el límite del
-- doc 04 §5; si en el futuro se necesita más, se sube en una migración.

CREATE TABLE comments (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    level_id        BIGINT UNSIGNED NULL,
    body            VARCHAR(400) NOT NULL,
    is_visible      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comments_user_id (user_id),
    KEY idx_comments_level_id (level_id),
    CONSTRAINT fk_comments_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_comments_level FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
