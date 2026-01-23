-- Inventário físico cíclico (VISTA KPI 2.0)
-- Cria tabelas mínimas para armários, ciclos e itens de inventário

CREATE TABLE IF NOT EXISTS armarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(64) NOT NULL UNIQUE,
  descricao VARCHAR(255) DEFAULT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventario_ciclos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mes_ano VARCHAR(7) NOT NULL, -- formato YYYY-MM
  aberto_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  encerrado_at DATETIME DEFAULT NULL,
  encerrado_por INT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS inventario_itens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  remessa_id INT NOT NULL,
  armario_id INT DEFAULT NULL,
  ciclo_id INT DEFAULT NULL,
  status ENUM('aguardando_pg','confirmado','nao_encontrado') NOT NULL DEFAULT 'aguardando_pg',
  usuario_id INT DEFAULT NULL,
  observacao VARCHAR(255) DEFAULT NULL,
  confirmado_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (remessa_id),
  INDEX (armario_id),
  INDEX (ciclo_id)
);

-- Nota: remessa_id referencia a tabela resumo_geral (assumida existente no sistema)
