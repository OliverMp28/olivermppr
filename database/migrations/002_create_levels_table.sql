-- Tabla levels — niveles generados por el usuario a partir de sus MP3s.
--
-- file_path nullable + is_public default FALSE: el modelo es Local-First
-- (Bloque 7 doc 08). Solo cuando el usuario marca un nivel como público SÍ se
-- sube el MP3 al servidor. El CHECK garantiza que no puede haber nivel público
-- sin archivo asociado.
--
-- generator_seed VARCHAR(64): suficiente para hash hex de 256 bits. Se usa
-- para regenerar la misma ObstacleTimeline a partir del MP3 (Bloque 5).

CREATE TABLE levels (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    title           VARCHAR(120) NOT NULL,
    artist          VARCHAR(120) NULL,
    duration_sec    INT UNSIGNED NOT NULL,
    bpm             SMALLINT UNSIGNED NULL,
    difficulty      TINYINT UNSIGNED NOT NULL,
    file_path       VARCHAR(500) NULL,
    generator_seed  VARCHAR(64) NOT NULL,
    is_public       BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_levels_user_id (user_id),
    CONSTRAINT chk_levels_difficulty CHECK (difficulty BETWEEN 1 AND 5),
    CONSTRAINT chk_levels_public_has_file CHECK (is_public = FALSE OR file_path IS NOT NULL),
    CONSTRAINT fk_levels_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
