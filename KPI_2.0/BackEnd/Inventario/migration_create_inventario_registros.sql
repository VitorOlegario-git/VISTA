-- Migration: cria tabela inventario_registros para registrar confirmações de inventário por ciclo

CREATE TABLE IF NOT EXISTS inventario_registros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ciclo_id INT NULL,
  armario_id INT NULL,
  resumo_id INT NULL,
  inventariado_por INT NULL,
  observacao VARCHAR(255) DEFAULT NULL,
  inventariado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_ciclo_resumo (ciclo_id, resumo_id),
  INDEX (ciclo_id),
  INDEX (armario_id),
  INDEX (resumo_id)
);
