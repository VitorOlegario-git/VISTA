Seleção de Armários — README de teste

Propósito
- Documentar rapidamente como testar a nova tela de seleção de armários e o arquivo de simulação criado para validação manual.

Arquivos criados
- FrontEnd/html/inventario_armarios.php  — Tela de produção (consome /router_public.php?url=inventario/armarios-api)
- FrontEnd/html/inventario_armarios_test.html — Página de teste local que simula respostas da API
- BackEnd: (nenhuma modificação adicional neste README; backend esperado: GET /router_public.php?url=inventario/armarios-api)

Endpoint esperado (contrato)
GET /router_public.php?url=inventario/armarios-api
Resposta JSON esperada:
{
  "success": true,
  "ciclo": { "id": 12, "mes_ano": "2026-01", "aberto_at": "2026-01-01 08:00:00" },
  "prev_open": { "id": 11, "mes_ano": "2025-12" },
  "armarios": [ { "id":3, "codigo":"ARM-01", "descricao":"Armário Recepção", "pendentes":12 } ]
}

Testes locais
1) Via servidor PHP embutido (recomendado):
```powershell
cd "z:\KPI_2.0"
php -S localhost:8000
```
Abra então no navegador:
http://localhost:8000/FrontEnd/html/inventario_armarios_test.html

2) Abrir diretamente o arquivo (file://):
- Você pode abrir FrontEnd/html/inventario_armarios_test.html diretamente, porém links absolutos de navegação podem não funcionar sem servidor.

O que a página de teste faz
- Possui botões para simular: sucesso (com ciclo ativo + ciclo anterior aberto), sem ciclo ativo, payload com success=false, e 401 (sessão expirada).
- Permite validar visualmente estados: loading, error (inline), empty (bloqueado quando não há ciclo), e list.

Observações de produção
- `inventario_armarios.php` usa fetch com `credentials: 'same-origin'` — a sessão é necessária para autenticação. Em 401 a UI mostra aviso de sessão expirada com link para login.
- O arquivo de teste `inventario_armarios_test.html` é apenas para validação manual e pode ser removido após verificação.
- Não há lógica de negócio no frontend: seleção redireciona para `/router_public.php?url=inventario/iniciar&armario_id=X` que deve ser tratada pelo backend.

Limpeza após validação
- Remover o arquivo de teste se desejar:
```powershell
rm "FrontEnd/html/inventario_armarios_test.html"
```

Suporte
- Se a API retornar `success:false` ou JSON inválido, a página exibirá mensagem inline em vez de alert.
- Se precisar, posso adicionar link no menu/navbar apontando para `FrontEnd/html/inventario_armarios.php`.

Fim.
