CREATE TABLE IF NOT EXISTS revoked_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    jti VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_revoked_tokens_jti (jti),
    KEY idx_revoked_tokens_expires_at (expires_at)
);
