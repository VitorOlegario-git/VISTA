-- Estrutura da tabela de auditoria para inventário
CREATE TABLE inventario_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    acao VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    remessas TEXT NOT NULL,
    datahora DATETIME NOT NULL
);

-- Exemplo de tabela de remessas (ajuste conforme seu modelo real)
CREATE TABLE remessas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(255) NOT NULL,
    nota_fiscal VARCHAR(100) NOT NULL,
    quantidade_pecas INT NOT NULL,
    status VARCHAR(50) NOT NULL
);