-- Migration: cria tabela de auditoria para atribuições iniciais de armário
-- Não adiciona FK por ora

CREATE TABLE IF NOT EXISTS inventario_atribuicoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resumo_id INT NOT NULL,
  armario_id INT NOT NULL,
  atribuido_por INT NOT NULL,
  atribuido_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (resumo_id),
  INDEX (armario_id)
);
