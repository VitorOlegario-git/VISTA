# VISTA KPI — README Arquitetural Oficial

## Visão Geral
O VISTA KPI é uma plataforma modular para monitoramento, análise e evolução de indicadores operacionais, preparada para expansão futura com IA preditiva (KPI 4.0).

## Estrutura de Pastas (KPI 3.0+)
```
DashBoard/
├─ core/         # Estado global, contratos, utilitários base
├─ services/     # Integração backend, fetch, KpiService
├─ engines/      # Engines de negócio: alertas, insights, score, timeseries
├─ components/   # Componentes visuais reutilizáveis (KpiCard, SidebarKpi, etc.)
├─ dashboards/   # Composição de telas (executivo, operacional, drilldown)
├─ examples/     # Exemplos de uso, scripts de referência (NUNCA importados em produção)
├─ mocks/        # Dados fake, mocks para dev/teste (NUNCA importados em produção)
├─ legacy/       # Código antigo, para futura refatoração ou remoção
```

## Princípios Arquiteturais
- **Separação de responsabilidades:**
  - Engines e services nunca conhecem UI.
  - Dashboards orquestram, não calculam.
- **Extensibilidade:**
  - Pronto para novos KPIs, engines e integrações.
- **Evolução segura:**
  - Refatoração controlada, sem impacto funcional.
- **Observabilidade:**
  - Logs, tratamento de erro, suporte a cancelamento.
- **Preparação para IA:**
  - Estruturas de dados históricas padronizadas (timeseries)
  - Engines desacopladas para insights e score

## Fluxo de Dependências
```
core → engines → services → components → dashboards
(dashboards podem orquestrar qualquer camada, mas nunca o inverso)
```
- **examples/** e **mocks/**: nunca importados por produção.
- **legacy/**: não referenciado por código novo.

## Checklist de Qualidade
- Imports relativos sempre atualizados após refatoração
- Nenhum erro de import no console
- Find in Files: "examples/", "mocks/" só dentro das próprias pastas
- Dashboards principais carregam e navegam sem erro

## Como evoluir para KPI 4.0
- Engines e contratos já preparados para séries temporais e predição
- Adicionar novos engines (ex: predição, automação) sem quebrar UI
- Manter mocks/examples isolados

## Onboarding
- Leia este README antes de contribuir
- Siga o checklist operacional final
- Consulte exemplos em **examples/**, nunca importe em produção

---

**Este README é o documento de referência para arquitetura e manutenção do VISTA KPI.**
