-- Migration: adiciona coluna armario_id em resumo_geral
-- Execute após backup do banco

ALTER TABLE resumo_geral
  ADD COLUMN armario_id INT NULL COMMENT 'Referência a BackEnd/Inventario/armarios.id',
  ADD INDEX idx_resumo_armario (armario_id);

-- Observação: se desejar manter integridade referencial, adicione FK:
-- ALTER TABLE resumo_geral ADD CONSTRAINT fk_resumo_armario FOREIGN KEY (armario_id) REFERENCES armarios(id) ON DELETE SET NULL;
