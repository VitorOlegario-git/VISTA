-- Migration: Create table to log expired temporary user tokens
-- Generated: 2026-01-21

CREATE TABLE IF NOT EXISTS usuarios_temp_expired_log (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(128) NOT NULL,
    nome VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    token_created_at DATETIME DEFAULT NULL,
    removed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(50) DEFAULT 'expired',
    removed_by VARCHAR(50) DEFAULT NULL,
    INDEX (email),
    INDEX (removed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: grant minimal privileges to the app user if needed (run as admin)
-- Example: GRANT INSERT, SELECT, DELETE ON `yourdb`.`usuarios_temp_expired_log` TO 'appuser'@'host';
