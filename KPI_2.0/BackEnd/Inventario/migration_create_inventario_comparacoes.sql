-- Migration: cria tabela inventario_comparacoes para registrar resultados de comparações manuais
CREATE TABLE IF NOT EXISTS inventario_comparacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ciclo_id INT NOT NULL,
  armario_id INT NULL,
  resumo_id INT NULL,
  remessa VARCHAR(128) NOT NULL,
  status_inventario VARCHAR(32) NOT NULL,
  status_banco VARCHAR(64) NULL,
  observacao VARCHAR(512) NULL,
  criado_por INT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (ciclo_id),
  INDEX (armario_id),
  INDEX (resumo_id),
  INDEX idx_ciclo_armario (ciclo_id, armario_id),
  INDEX idx_remessa_ciclo (remessa(64), ciclo_id)
);
