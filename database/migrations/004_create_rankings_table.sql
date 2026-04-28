-- Tabla rankings — agregado denormalizado por usuario para lecturas rápidas.
--
-- Doc 04 §5 contempla esto como "tabla física vs VIEW": elegimos tabla física
-- + recálculo desde el modelo (Bloque 7) tras cada Progress::upsert. Ganamos
-- lecturas O(1) para el ranking global a costa de coherencia eventual de
-- segundos. Si en el futuro hace falta refrescar el agregado por triggers,
-- se añade en una migración posterior.

CREATE TABLE rankings (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL,
    total_points    INT UNSIGNED NOT NULL DEFAULT 0,
    avg_percentage  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    levels_played   INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_rankings_user_id (user_id),
    CONSTRAINT chk_rankings_avg_percentage CHECK (avg_percentage BETWEEN 0 AND 100),
    CONSTRAINT fk_rankings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
