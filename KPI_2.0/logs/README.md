# Diretório de Logs
Este diretório armazena os logs da aplicação.

**IMPORTANTE**: Este diretório deve ter permissões de escrita para o servidor web.

## Logs Criados:
- `php_errors.log` - Erros do PHP
- `kpi.log` - Execuções de KPIs (novo - 15/01/2026)
- `auth.log` - Eventos de autenticação
- `access.log` - Log de acessos (futuro)
- `security.log` - Eventos de segurança (futuro)

## Formato do kpi.log:
```
[TIMESTAMP] [KPI_NAME] [STATUS] periodo=DD/MM/YYYY-DD/MM/YYYY operador=NOME executionTimeMs=XXX
```

Exemplo:
```
[2026-01-15 10:30:45] [kpi-backlog-atual] [SUCCESS] periodo=07/01/2026-14/01/2026 operador=Todos executionTimeMs=245
[2026-01-15 10:31:02] [kpi-tempo-medio] [ERROR] periodo=01/01/2026-31/01/2026 operador=Todos executionTimeMs=0 message="Database connection failed"
```
