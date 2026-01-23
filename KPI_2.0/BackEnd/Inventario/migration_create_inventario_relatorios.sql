-- Migration: cria tabela inventario_relatorios para snapshots ao encerrar ciclo

CREATE TABLE IF NOT EXISTS inventario_relatorios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ciclo_id INT NOT NULL,
  criado_por INT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  snapshot LONGTEXT NOT NULL, -- JSON snapshot
  INDEX (ciclo_id)
);
