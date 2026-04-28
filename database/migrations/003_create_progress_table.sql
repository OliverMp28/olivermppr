-- Tabla progress — mejor partida de cada (user, level).
--
-- UNIQUE(user_id, level_id): un usuario solo guarda su mejor run por nivel.
-- Las inserciones en runtime usan upsert (INSERT ... ON DUPLICATE KEY UPDATE).
-- percentage 0..100, points sin techo explícito (la lógica de scoring vive en
-- el Bloque 5/7, no aquí).

CREATE TABLE progress (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    level_id        BIGINT UNSIGNED NOT NULL,
    percentage      TINYINT UNSIGNED NOT NULL,
    points          INT UNSIGNED NOT NULL,
    best_run_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_progress_user_level (user_id, level_id),
    KEY idx_progress_user_id (user_id),
    KEY idx_progress_level_id (level_id),
    CONSTRAINT chk_progress_percentage CHECK (percentage BETWEEN 0 AND 100),
    CONSTRAINT fk_progress_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_progress_level FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
