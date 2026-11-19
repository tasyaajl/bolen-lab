-- =========================================================
-- Migrasi P1 — kunci pengaturan baru (tenggat bayar, kebijakan batal)
-- Idempoten: baris yang sudah ada tidak diubah.
-- =========================================================

INSERT IGNORE INTO business_settings (setting_key, setting_value)
VALUES ('payment_due_hours', '24');

INSERT IGNORE INTO business_settings (setting_key, setting_value)
VALUES ('cancellation_policy', NULL);
