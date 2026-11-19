-- =========================================================
-- Rollback migrasi 20261119_p0_security
-- PERINGATAN: menghapus token akses, bukti bayar, dan data throttle.
-- Jalankan hanya bila migrasi benar-benar dibatalkan.
-- =========================================================

DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS login_attempts;

ALTER TABLE orders
    DROP INDEX uq_orders_access_token,
    DROP COLUMN access_token,
    DROP COLUMN payment_due_at,
    DROP COLUMN payment_proof,
    DROP COLUMN payment_proof_at,
    DROP COLUMN cancel_reason;
