# Auditoria — Remessas (codigo_remessa → resumo_id)

Data de geração: ______________
Ambiente: staging / production (circle one) ______________

---

## 1) Validação de segurança do SQL
- Arquivo auditado: `scripts/audit_remessa_queries.sql`
- Resultado da verificação: o arquivo contém apenas comentários e instruções `SELECT`.
- Confirmação: não há ocorrências de `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `CREATE`.
- Conclusão: o arquivo é 100% READ-ONLY e seguro para execução em produção (por um DBA/operador autorizado).

> Observação: executar com um usuário somente-leitura é recomendado; evitar server-side OUTFILEs sem coordenação.

---

## 2) Instruções de execução (para DBA)

Notas gerais
- Execute cada bloco separadamente (recomendado) para inspeção e export.
- Preferir conta com privilégios apenas de leitura.
- Usar cliente CLI (mysql) ou ferramenta GUI (MySQL Workbench, DBeaver).

CLI (mysql) — export CSV com cabeçalho (exemplos)
- Ajuste `-h`, `-u`, `-p` e `-D` conforme ambiente. Use `--default-character-set=utf8mb4` se necessário.

1) Mapeamento completo (bloco 1.1)
```bash
mysql -h <host> -u <user> -p -D <db> --default-character-set=utf8mb4 --batch --raw --execute "SELECT id AS resumo_id, codigo_remessa, CHAR_LENGTH(codigo_remessa) AS len, (codigo_remessa REGEXP '^[[:space:]]|[[:space:]]$') AS has_edge_whitespace FROM resumo_geral ORDER BY codigo_remessa, id;" > audit_map.csv
```

2) Duplicidades exatas (bloco 1.2)
```bash
mysql -h <host> -u <user> -p -D <db> --default-character-set=utf8mb4 --batch --raw --execute "SELECT codigo_remessa, COUNT(*) AS qtd, GROUP_CONCAT(id ORDER BY id) AS resumo_ids FROM resumo_geral WHERE codigo_remessa IS NOT NULL AND codigo_remessa <> '' GROUP BY codigo_remessa HAVING COUNT(*) > 1 ORDER BY qtd DESC, codigo_remessa;" > audit_duplicates.csv
```

3) Colisões por normalização (bloco 1.4)
```bash
mysql -h <host> -u <user> -p -D <db> --default-character-set=utf8mb4 --batch --raw --execute "SELECT LOWER(TRIM(codigo_remessa)) AS codigo_norm, COUNT(*) AS qtd, GROUP_CONCAT(id ORDER BY id) AS resumo_ids, GROUP_CONCAT(codigo_remessa ORDER BY id SEPARATOR ' | ') AS valores_originais FROM resumo_geral WHERE codigo_remessa IS NOT NULL AND TRIM(codigo_remessa) <> '' GROUP BY LOWER(TRIM(codigo_remessa)) HAVING COUNT(*) > 1 ORDER BY qtd DESC, codigo_norm;" > audit_norm_collisions.csv
```

4) Vazios / nulos / somente whitespace (bloco 1.3)
```bash
mysql -h <host> -u <user> -p -D <db> --default-character-set=utf8mb4 --batch --raw --execute "SELECT id AS resumo_id, codigo_remessa FROM resumo_geral WHERE codigo_remessa IS NULL OR codigo_remessa = '' OR TRIM(codigo_remessa) = '';" > audit_empty.csv
```

Quick summary counts (recommended to get numbers quickly)
```bash
# Total registros
mysql -h <host> -u <user> -p -D <db> -sN -e "SELECT COUNT(*) FROM resumo_geral;"

# Duplicidades exatas (número de codigo_remessa com >1 id)
mysql -h <host> -u <user> -p -D <db> -sN -e "SELECT COUNT(*) FROM (SELECT codigo_remessa FROM resumo_geral WHERE codigo_remessa IS NOT NULL AND codigo_remessa <> '' GROUP BY codigo_remessa HAVING COUNT(*)>1) t;"

# Colisões normalizadas (número de codigo_norm com >1 id)
mysql -h <host> -u <user> -p -D <db> -sN -e "SELECT COUNT(*) FROM (SELECT LOWER(TRIM(codigo_remessa)) AS codigo_norm FROM resumo_geral WHERE codigo_remessa IS NOT NULL AND TRIM(codigo_remessa) <> '' GROUP BY codigo_norm HAVING COUNT(*)>1) t;"

# Vazios / nulos
mysql -h <host> -u <user> -p -D <db> -sN -e "SELECT COUNT(*) FROM resumo_geral WHERE codigo_remessa IS NULL OR codigo_remessa = '' OR TRIM(codigo_remessa) = '';"
```

GUI (MySQL Workbench / DBeaver)
- Abra `scripts/audit_remessa_queries.sql`.
- Selecione o primeiro SELECT (bloco 1.1) e execute; exporte resultado como CSV (botão direito → Export Resultset).
- Repita para os demais blocos.

---

## 3) Consolidação dos Resultados (preencher após execução)

**Instruções:** cole abaixo os números obtidos (ou anexe os CSVs gerados).

- Total de registros (resultado do COUNT ou linhas em `audit_map.csv`): ______________
- Duplicidades exatas (linhas em `audit_duplicates.csv`): ______________
- Colisões por normalização (linhas em `audit_norm_collisions.csv`): ______________
- Vazios / nulos (linhas em `audit_empty.csv`): ______________

Observações / exemplos (cole amostras relevantes, p.ex. 5 linhas de `audit_duplicates.csv`):

```
(Cole amostras aqui)
```

---

## 4) Classificação do estado do banco (a ser preenchido pela IA ao receber os números)
- Classificação final: 🟢 / 🟡 / 🔴  (preencher após receber métricas)
- Justificativa:
  - Duplicidades exatas: ____
  - Colisões normalizadas: ____
  - Vazios/nulos: ____

---

## 5) Relatório Técnico — Estrutura (será gerado automaticamente when results provided)

5.1 Resumo Executivo
- Objetivo da auditoria: mapear `codigo_remessa` → `resumo_id`, detectar duplicidades/colisões/vazios.
- Data da execução: ______________
- Ambiente: staging / production
- Classificação final: 🟢 / 🟡 / 🔴

5.2 Metodologia
- Execução dos blocos SELECT do arquivo `scripts/audit_remessa_queries.sql` (blocos 1.1..1.4).
- Confirmação de READ-ONLY: arquivo contém apenas SELECTs.

5.3 Resultados Detalhados
- (Inserir tabela resumo com métricas preenchidas acima)

5.4 Impacto no Inventário
- Riscos identificados: (preencher com base nos CSVs)
- Fluxos afetados: conciliação (`ConsolidacaoApi.php`), inventário, relatórios.

5.5 Recomendação Técnica
- O que pode ser feito agora: (depende da classificação — IA preencherá when numbers are provided)
- O que NÃO deve ser feito: alterações de escrita/UNIQUE sem limpeza prévia.
- Próxima ação recomendada: (limpeza / re-auditoria / prosseguir)

---

## 6) Checklist pós-execução (operador)
- [ ] Exportei `audit_map.csv`, `audit_duplicates.csv`, `audit_norm_collisions.csv`, `audit_empty.csv`.
- [ ] Preenchi os números no campo "Consolidação dos Resultados" acima.
- [ ] Copiei/amostrei 5 exemplos relevantes para `audit_duplicates.csv` e `audit_norm_collisions.csv`.
- [ ] Enviei apenas o resumo numérico para o time de decisão.

---

## 7) Observações finais
- Não executar scripts de escrita neste passo.
- Esta auditoria é pré-requisito obrigatório antes de qualquer UNIQUE ou mudança de escrita no inventário.

*** Fim do relatório/template — pronto para preenchimento pelo operador ***
