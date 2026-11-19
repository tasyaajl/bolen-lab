-- =========================================================
-- Migrasi P0 — keamanan pesanan, bukti bayar, expiry, throttle
-- Berlaku untuk database yang sudah berisi data.
-- Rollback: database/migrations/20261119_p0_security_rollback.sql
-- =========================================================

ALTER TABLE orders
    ADD COLUMN access_token VARCHAR(64) NULL AFTER order_code,
    ADD COLUMN payment_due_at DATETIME NULL AFTER payment_status,
    ADD COLUMN payment_proof VARCHAR(255) NULL AFTER payment_due_at,
    ADD COLUMN payment_proof_at DATETIME NULL AFTER payment_proof,
    ADD COLUMN cancel_reason VARCHAR(255) NULL AFTER stock_restored;

-- Pesanan lama tetap dapat diakses lewat verifikasi 4 digit WhatsApp.
-- Token acak hanya untuk tautan yang dibagikan.
UPDATE orders
SET access_token = SHA2(CONCAT(id, '-', order_code, '-', RAND()), 256)
WHERE access_token IS NULL;

ALTER TABLE orders
    ADD UNIQUE KEY uq_orders_access_token (access_token);

CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(150) NOT NULL,
    ip           VARCHAR(45) NOT NULL,
    successful   TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_attempts_email (email, attempted_at),
    KEY idx_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket       VARCHAR(60) NOT NULL,
    key_hash     CHAR(64) NOT NULL,
    hits         INT UNSIGNED NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL,
    PRIMARY KEY (bucket, key_hash),
    KEY idx_rate_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
