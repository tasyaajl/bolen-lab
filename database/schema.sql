-- =========================================================
-- BaleBolen — Skema Database MySQL 8+
-- Jalankan: mysql -u root -p < database/schema.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS balebolen
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE balebolen;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_item_variants;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS business_settings;
DROP TABLE IF EXISTS admins;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Admin
-- ---------------------------------------------------------
CREATE TABLE admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Pengaturan bisnis (key-value agar mudah dilengkapi)
-- ---------------------------------------------------------
CREATE TABLE business_settings (
    setting_key   VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Kategori produk
-- ---------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(80)  NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Varian rasa + stok (sumber stok tunggal untuk satuan & hampers)
-- ---------------------------------------------------------
CREATE TABLE product_variants (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(80) NOT NULL,
    slug       VARCHAR(80) NOT NULL,
    stock      INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_variants_slug (slug),
    KEY idx_variants_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Produk (bolen satuan & paket hampers)
-- price NULL = harga belum ditetapkan admin (tidak boleh transaksi)
-- ---------------------------------------------------------
CREATE TABLE products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED NOT NULL,
    variant_id      INT UNSIGNED NULL,
    name            VARCHAR(120) NOT NULL,
    slug            VARCHAR(140) NOT NULL,
    description     TEXT NULL,
    type            ENUM('single','hampers') NOT NULL DEFAULT 'single',
    price           DECIMAL(12,2) NULL,
    package_size    INT UNSIGNED NULL,
    packaging_info  VARCHAR(255) NULL,
    image           VARCHAR(255) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (category_id),
    KEY idx_products_type_active (type, is_active),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT fk_products_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Pesanan
-- ---------------------------------------------------------
CREATE TABLE orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code       VARCHAR(24) NOT NULL,
    access_token     VARCHAR(64) NULL,
    customer_name    VARCHAR(100) NOT NULL,
    whatsapp         VARCHAR(20) NOT NULL,
    fulfillment      ENUM('pickup','delivery') NOT NULL,
    delivery_method  ENUM('own','gojek','other') NULL,
    delivery_address TEXT NULL,
    delivery_date    DATE NOT NULL,
    notes            TEXT NULL,
    gift_recipient   VARCHAR(100) NULL,
    gift_message     VARCHAR(300) NULL,
    subtotal         DECIMAL(12,2) NOT NULL,
    shipping_cost    DECIMAL(12,2) NULL,
    total            DECIMAL(12,2) NOT NULL,
    payment_method   ENUM('transfer','qris','cod','cash','gopay') NOT NULL,
    payment_status   ENUM('unpaid','awaiting_verification','paid','cancelled') NOT NULL DEFAULT 'unpaid',
    payment_due_at   DATETIME NULL,
    payment_proof    VARCHAR(255) NULL,
    payment_proof_at DATETIME NULL,
    order_status     ENUM('new','awaiting_payment','confirmed','processing','ready_for_pickup','shipping','completed','cancelled') NOT NULL DEFAULT 'new',
    stock_restored   TINYINT(1) NOT NULL DEFAULT 0,
    cancel_reason    VARCHAR(255) NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_code (order_code),
    UNIQUE KEY uq_orders_access_token (access_token),
    KEY idx_orders_status (order_status),
    KEY idx_orders_payment_status (payment_status),
    KEY idx_orders_created (created_at),
    KEY idx_orders_delivery_date (delivery_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Item pesanan
-- ---------------------------------------------------------
CREATE TABLE order_items (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id         INT UNSIGNED NOT NULL,
    product_id       INT UNSIGNED NULL,
    product_name     VARCHAR(120) NOT NULL,
    product_type     ENUM('single','hampers') NOT NULL,
    package_size     INT UNSIGNED NULL,
    unit_price       DECIMAL(12,2) NOT NULL,
    quantity         INT UNSIGNED NOT NULL,
    line_total       DECIMAL(12,2) NOT NULL,
    composition_text VARCHAR(255) NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL,
    KEY idx_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Komposisi varian per item (terstruktur, bukan teks bebas)
-- ---------------------------------------------------------
CREATE TABLE order_item_variants (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT UNSIGNED NOT NULL,
    variant_id    INT UNSIGNED NULL,
    variant_name  VARCHAR(80) NOT NULL,
    quantity      INT UNSIGNED NOT NULL,
    CONSTRAINT fk_oiv_item FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE,
    CONSTRAINT fk_oiv_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE SET NULL,
    KEY idx_oiv_item (order_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Riwayat pergerakan stok
-- ---------------------------------------------------------
CREATE TABLE stock_movements (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id  INT UNSIGNED NOT NULL,
    change_qty  INT NOT NULL,
    stock_after INT NOT NULL,
    reason      ENUM('restock','order','cancel','adjustment') NOT NULL,
    note        VARCHAR(255) NULL,
    order_id    INT UNSIGNED NULL,
    admin_id    INT UNSIGNED NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movements_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE,
    CONSTRAINT fk_movements_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL,
    CONSTRAINT fk_movements_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL,
    KEY idx_movements_variant (variant_id),
    KEY idx_movements_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Percobaan login admin (throttle per email + IP, lintas session)
-- ---------------------------------------------------------
CREATE TABLE login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(150) NOT NULL,
    ip           VARCHAR(45) NOT NULL,
    successful   TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_attempts_email (email, attempted_at),
    KEY idx_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Pembatas laju generik (checkout, verifikasi akses pesanan, unggah bukti)
-- ---------------------------------------------------------
CREATE TABLE rate_limits (
    bucket       VARCHAR(60) NOT NULL,
    key_hash     CHAR(64) NOT NULL,
    hits         INT UNSIGNED NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL,
    PRIMARY KEY (bucket, key_hash),
    KEY idx_rate_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
