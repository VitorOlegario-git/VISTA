Inventário Físico Cíclico — VISTA KPI 2.0

Arquivos adicionados:
- inventario_schema.sql : esquema SQL para criar tabelas `armarios`, `inventario_ciclos`, `inventario_itens`.
- Armario.php : endpoint POST para cadastrar armários (CSRF + sessão + prepared statements).
- AtribuirArmario.php : endpoint POST para atribuir em lote `armario_id` em `resumo_geral` (carga inicial).
- InventarioApi.php : API AJAX para listar itens por armário, confirmar presença, marcar não encontrado, e finalizar ciclo.

Front-end:
- FrontEnd/html/armarios.php : tela para cadastrar e listar armários.
- FrontEnd/html/atribuir_armario.php : tela para atribuição inicial (seleção múltipla).
- inventario.php : página mobile-first utilizada via QR Code (`/inventario.php?armario=ARM-01`).

Como aplicar o esquema SQL:
1. Fazer backup do banco.
2. Executar `BackEnd/Inventario/inventario_schema.sql` no banco do KPI.

Notas de segurança e operação:
- Todos os endpoints verificam sessão e CSRF quando necessário.
- Operações de inventário não alteram status financeiro.
- Inventário só é validado após encerramento manual de ciclo.

Próximos passos recomendados:
- Criar rotinas administrativas para criar/abrir ciclos com mes_ano automático.
- Adicionar visualização histórica e auditoria por usuário/horário.
