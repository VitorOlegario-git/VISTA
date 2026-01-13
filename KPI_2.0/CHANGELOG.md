# 📝 Histórico de Alterações - Sistema VISTA

## [3.0.0] - 13 de Janeiro de 2026

### 🎯 KPI 3.0 - Indicadores Refinados
- **Sistema de KPIs com Contexto e Julgamento:**
  - Valor absoluto + Referência (média 30d / meta / período anterior)
  - Variação percentual com direção (↑ ↓ →)
  - Estado automático (success / warning / critical)
  - Contrato padronizado para todos os KPIs

### ✨ Backend - Endpoint Helpers
- **Novas funções em `BackEnd/endpoint-helpers.php`:**
  - `calcularVariacao()` - Calcula variação percentual entre valor atual e referência
  - `definirDirecao()` - Define direção da variação (up/down/stable)
  - `definirEstado()` - Define estado baseado em limites (success/warning/critical)
  - `definirEstadoInvertido()` - Para métricas onde aumento é negativo
  - `montarKpiRefinado()` - Estrutura completa de KPI 3.0

### 🔄 Backend - KPIs Globais Atualizados
- **kpi-total-processado.php:**
  - Compara com média dos últimos 30 dias
  - Estado: ±10% success, 10-25% warning, >25% critical
  
- **kpi-tempo-medio.php:**
  - SLA de 7200 minutos (5 dias)
  - Estado baseado em SLA e variação
  - Conversão automática minutos → horas
  
- **kpi-taxa-sucesso.php:**
  - Meta: ≥85% success, 70-84% warning, <70% critical
  - Compara com média 30 dias
  
- **kpi-sem-conserto.php:**
  - Aumento acima da média = warning/critical
  - Estado invertido (menos é melhor)
  
- **kpi-valor-orcado.php:**
  - Compara com período anterior (mesmo número de dias)
  - Queda >25% critical, >10% warning

### 🎨 Frontend - Dashboard Executivo
- **HTML Estrutura Atualizada (`DashRecebimento.php`):**
  - Cards de KPI com IDs únicos para aplicar estados
  - Elementos `<span class="kpi-variacao">` para mostrar variação
  - Sistema de classes dinâmicas para estados visuais

- **Funções JavaScript:**
  - `renderizarKPIRefinado()` - Renderiza KPI com variação, cores e ícones
  - `gerarInsightsAPartirDosKPIs()` - Gera insights baseados em estados dos KPIs
  - `montarVisaoPorArea()` - Calcula volumes e estados por área
  - Remoção de funções legadas (insights/visão mockadas)

### 💡 Insights 2.0
- **Geração Inteligente:**
  - Analisa estado de cada KPI (critical/warning/success)
  - Prioriza: critical > warning > success
  - Máximo de 3 insights simultâneos
  - Mensagens contextuais com variação e percentuais
  - Fallback: "Operação normal" se todos os KPIs estiverem ok

- **Insights Implementados:**
  - Volume acima/abaixo da capacidade
  - SLA ultrapassado ou próximo do limite
  - Taxa de sucesso crítica ou excelente
  - Alto índice sem conserto
  - Queda ou crescimento em orçamentos

### 📊 Visão por Área 2.0
- **Dados Derivados dos KPIs:**
  - Volumes: Recebimento (100%), Análise (87%), Reparo (81%), Qualidade (74%)
  - Estados herdados dos KPIs relevantes
  - Tempos calculados proporcionalmente
  - Status dinâmico: normal/atencao/critico

- **Áreas Atualizadas:**
  - Recebimento: Estado baseado no volume total
  - Análise: Estado baseado no tempo médio
  - Reparo: Estado baseado no tempo médio
  - Qualidade: Estado baseado na taxa de sucesso
  - Financeiro: Estado baseado no valor orçado

### 🎨 CSS - Estilos Visuais
- **Novos estilos em `dashrecebimento.css`:**
  - `.kpi-variacao` - Exibe variação com cores contextuais
  - `.kpi-global-card.kpi-success` - Borda verde, background sutil
  - `.kpi-global-card.kpi-warning` - Borda amarela, background alaranjado
  - `.kpi-global-card.kpi-critical` - Borda vermelha, animação pulse
  - `.insight-card.insight-critical` - Com animação pulse-insight
  - Animações `@keyframes pulse-critical` e `pulse-insight`

### 🐛 Correções de Bugs
- **buscar_cliente.php:**
  - Removida referência a variável indefinida `APP_DEBUG`
  - Corrigido caminho duplicado do conexao.php
  - Adicionado `display_errors` para debug

- **Formulários - Máscaras CNPJ:**
  - Adicionada inicialização `initializeCNPJMask()` em:
    - recebimento.php
    - analise.php
    - reparo.php
    - qualidade.php

- **Formulários - Campo Setor:**
  - Corrigidos valores de setor em recebimento.php:
    - De: 'manutencao', 'devolucao', etc.
    - Para: 'manut-varejo', 'dev-varejo', 'manut-datora', etc.

- **Backend - Caminhos de Arquivo:**
  - Substituído `$_SERVER['DOCUMENT_ROOT']` por `dirname(__DIR__)` em 20+ arquivos
  - Script PowerShell para correção em massa de 17 arquivos
  - Arquivos corrigidos:
    - BackEnd/atualizar_status.php
    - BackEnd/buscar_cliente.php
    - BackEnd/Recebimento/Recebimento.php
    - BackEnd/Analise/Analise.php
    - BackEnd/Reparo/Reparo.php
    - BackEnd/Qualidade/Qualidade.php
    - Todos os arquivos consulta_*.php em módulos
    - Todos os arquivos salvar_dados_no_banco*.php

- **JavaScript - Erros de Sintaxe:**
  - Corrigida função `executarFiltros` fora de escopo
  - Removido código duplicado em `carregarResumoAreas`
  - Simplificadas chamadas em `carregarResumoExecutivo`

### 🔧 Modificado
- **Fluxo de Carregamento:**
  - `carregarKPIsGlobais()` agora chama automaticamente:
    - `gerarInsightsAPartirDosKPIs()`
    - `montarVisaoPorArea()`
  - Removidas chamadas duplicadas de:
    - `carregarInsightsAutomaticos()` (legado)
    - `carregarResumoAreas()` (legado)

- **Cache de Dados:**
  - `dadosGlobaisCache` atualizado com estrutura KPI 3.0
  - Usado apenas para compatibilidade com código legado
  - Novos componentes usam diretamente respostas dos KPIs

### 📁 Organização de Arquivos
- **Criada pasta `_OLD_FILES/`:**
  - 14 arquivos *_old.php e *_old.css movidos
  - Separação clara entre código atual e legado
  - Arquivos movidos:
    - FrontEnd/html/*_old.php (6 arquivos)
    - FrontEnd/CSS/*_old.css (6 arquivos)
    - DashBoard/frontendDash/DashRecebimento_old.php
    - DashBoard/frontendDash/cssDash/dashrecebimento_old.css

### 🛡️ Segurança
- Mantido `display_errors = 1` temporariamente para debug
- ⚠️ **TODO:** Desabilitar `display_errors` em produção após validação completa

### 📋 Regras de Negócio - KPIs
**Total Processado:**
- ±10% vs média 30d → success
- 10-25% → warning
- >25% → critical (sobrecarga)

**Tempo Médio:**
- Dentro do SLA (5 dias) → success
- >80% do SLA → warning
- >SLA → critical

**Taxa de Sucesso:**
- ≥85% → success
- 70-84% → warning
- <70% → critical

**Sem Conserto:**
- Dentro da média → success
- +10-25% vs média → warning
- >+25% vs média → critical

**Valor Orçado:**
- Crescimento ou estável → success
- Queda 10-25% → warning
- Queda >25% → critical

### 🔄 Compatibilidade
- Todos os filtros globais continuam funcionando
- Layout do dashboard mantido (sem quebras visuais)
- Endpoints antigos compatíveis com nova estrutura
- Fallback visual em caso de erro (mostra `---`)

---

## [2.1.0] - 12 de Janeiro de 2026

### ✨ Adicionado
- **Sistema de URL Routing:** Implementado sistema de URLs amigáveis sem necessidade de mod_rewrite
  - Arquivo `router.php` - Classe Router com gerenciamento de rotas
  - Arquivo `router_public.php` - Front controller público
  - URLs limpas: `/router_public.php?url=dashboard` ao invés de `/FrontEnd/html/PaginaPrincipal.php`
  - Redirecionamentos automáticos de URLs antigas para novas
  - Página 404 personalizada e estilizada
  - Suporte para rotas com parâmetros via query string
  
- **Documentação:**
  - [URL_SIMPLES.md](URL_SIMPLES.md) - Guia completo do sistema de routing
  - [URL_REWRITING.md](URL_REWRITING.md) - Configuração avançada com mod_rewrite
  - Atualização do README.md com seção de URLs amigáveis
  - Atualização do DEVELOPER_GUIDE.md com instruções de routing

### 🔧 Modificado
- **Redirecionamentos atualizados em:**
  - `BackEnd/cadastro_realizado.php` - Redireciona para `/router_public.php?url=dashboard`
  - `FrontEnd/tela_login.php` - Login redireciona para dashboard via router
  - `FrontEnd/html/recebimento.php` - Botão voltar usa nova URL
  - `FrontEnd/html/analise.php` - Redirecionamentos para dashboard e cadastro-entrada
  - `FrontEnd/html/reparo.php` - Botão voltar usa nova URL
  - `FrontEnd/html/qualidade.php` - Botão voltar usa nova URL
  - `FrontEnd/html/expedicao.php` - Botão voltar usa nova URL
  - `FrontEnd/html/consulta.php` - Botão voltar usa nova URL

- **Caminhos de assets corrigidos:**
  - `FrontEnd/html/PaginaPrincipal.php` - Todos os botões agora usam `asset()` helper
  - Vídeo de fundo convertido para caminho absoluto
  - Imagens de botões (analise.png, reparo.png, etc.) usando caminhos absolutos

### 🛡️ Segurança
- `.htaccess` configurado para bloquear acesso a arquivos sensíveis
- Desabilitada listagem de diretórios
- Proteção adicional para arquivos `.env`, `.md`, `.log`, `.sql`

### 🐛 Corrigido
- Problema de caminhos relativos quebrados quando usando router
- Erros 404 em imagens e vídeo de fundo
- Redirecionamento após login mantinha URL antiga
- Assets não carregavam corretamente através do router

### 📋 Rotas Implementadas
```
/router_public.php?url=login              → FrontEnd/tela_login.php
/router_public.php?url=cadastro           → FrontEnd/CadastroUsuario.php
/router_public.php?url=recuperar-senha    → FrontEnd/RecuperarSenha.php
/router_public.php?url=nova-senha         → FrontEnd/NovaSenha.php
/router_public.php?url=confirmar-cadastro → FrontEnd/confirmar_cadastro.php
/router_public.php?url=logout             → BackEnd/logout.php
/router_public.php?url=dashboard          → FrontEnd/html/PaginaPrincipal.php
/router_public.php?url=home               → FrontEnd/html/PaginaPrincipal.php
/router_public.php?url=analise            → FrontEnd/html/analise.php
/router_public.php?url=recebimento        → FrontEnd/html/recebimento.php
/router_public.php?url=reparo             → FrontEnd/html/reparo.php
/router_public.php?url=qualidade          → FrontEnd/html/qualidade.php
/router_public.php?url=expedicao          → FrontEnd/html/expedicao.php
/router_public.php?url=consulta           → FrontEnd/html/consulta.php
/router_public.php?url=consulta/id        → FrontEnd/html/consulta_id.php
/router_public.php?url=cadastrar-cliente  → FrontEnd/html/cadastrar_cliente.php
/router_public.php?url=cadastro-entrada   → FrontEnd/html/cadastro_excel_entrada.php
/router_public.php?url=cadastro-pos-analise → FrontEnd/html/cadastro_excel_pos_analise.php
```

### 🔄 Compatibilidade
- URLs antigas continuam funcionando via redirecionamento 301
- Sistema funciona em qualquer hospedagem PHP sem necessidade de mod_rewrite
- Compatível com Apache, Nginx e outros servidores web

---

## [2.0.0] - Janeiro de 2026

### ✨ Adicionado
- Sistema de configuração centralizada (.env)
- Classe Database com padrão Singleton
- Classe Validator com 15+ métodos de validação
- Classe EmailService para envio centralizado de e-mails
- Sistema de helpers com funções auxiliares
- Proteção CSRF completa
- Headers de segurança HTTP
- Sistema de logging estruturado
- Documentação completa (5 arquivos)

### 🛡️ Segurança
- Credenciais movidas para .env
- Remoção de código de debug (file_put_contents)
- Desabilitado display_errors em produção
- Implementação de CSRF tokens
- Session regeneration em autenticação
- Prepared statements em todas as queries
- Validação de entrada centralizada
- Headers de segurança (X-Frame-Options, CSP, etc.)

### 🔧 Modificado
- BackEnd/conexao.php - Usa Database class
- BackEnd/buscar_cliente.php - Removido debug
- FrontEnd/tela_login.php - Usa helpers
- FrontEnd/CadastroUsuario.php - Usa Validator e EmailService
- FrontEnd/RecuperarSenha.php - Usa EmailService
- FrontEnd/confirmar_cadastro.php - Transações e melhor UX
- 13 arquivos atualizados no total

### 📚 Documentação
- README.md - Visão geral e quick start
- EXECUTIVE_SUMMARY.md - Resumo executivo
- SECURITY_IMPROVEMENTS.md - Melhorias de segurança
- DEVELOPER_GUIDE.md - Guia do desenvolvedor
- MIGRATION_GUIDE.md - Guia de migração
- TROUBLESHOOTING.md - Solução de problemas

### 🐛 Corrigido
- HTTP 500 error em tela_login.php (faltava require conexao.php)
- Sessões duplicadas em múltiplos arquivos
- URLs hardcoded sem usar constantes
- Email service inconsistente

---

## [1.0.0] - 2025
### Versão Inicial
- Sistema KPI 2.0 funcional
- Módulos: Recebimento, Análise, Reparo, Qualidade, Expedição, Consulta
- Dashboard com relatórios
- Sistema de login e autenticação
- Cadastro de usuários e clientes

---

## 🔮 Próximas Versões

### [2.2.0] - Planejado
- [ ] Migração completa dos módulos pendentes (Reparo, Qualidade, Expedição, Consulta)
- [ ] Sistema de rate limiting para APIs
- [ ] Autenticação em dois fatores (2FA)
- [ ] Sistema de permissões e roles
- [ ] Audit logs de todas as ações
- [ ] Testes automatizados (PHPUnit)
- [ ] CI/CD pipeline

### [3.0.0] - Futuro
- [ ] API REST completa
- [ ] Interface SPA (Vue.js/React)
- [ ] Websockets para atualizações em tempo real
- [ ] Sistema de notificações push
- [ ] Integração com Azure/AWS
- [ ] Containerização (Docker)

---

**Mantido por:** Equipe de Desenvolvimento Suntech  
**Última Atualização:** 12 de Janeiro de 2026
