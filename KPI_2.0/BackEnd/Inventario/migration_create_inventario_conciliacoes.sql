-- Migration: create inventario_conciliacoes
CREATE TABLE IF NOT EXISTS inventario_conciliacoes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ciclo_id INT NOT NULL,
  armario_id VARCHAR(64) NOT NULL,
  remessa VARCHAR(64) NOT NULL,
  status_inventario VARCHAR(64) DEFAULT NULL,
  status_banco VARCHAR(128) DEFAULT NULL,
  resultado VARCHAR(32) DEFAULT NULL,
  observacao TEXT DEFAULT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY ux_ciclo_armario_remessa (ciclo_id, armario_id, remessa),
  KEY idx_ciclo_armario (ciclo_id, armario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
