Testando o endpoint `inventario/conciliacao-api?action=compare_armario`

Pré-requisitos
- A aplicação web rodando (ex.: http://localhost)
- Sessão autenticada (cookie `PHPSESSID`) para um usuário com permissão
- Token CSRF válido para a sessão (meta ou hidden field)

Passos (manual)
1. Obter cookie de sessão (login via browser ou endpoint de login).
2. Obter token CSRF (carregar página que inclui `meta[name="csrf-token"]` ou usar campo hidden).

Exemplo `curl` (Linux/macOS):

```bash
curl -i -X POST 'http://localhost/router_public.php?url=inventario/conciliacao-api' \
  -b 'PHPSESSID=SEU_SESSION_ID' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'action=compare_armario' \
  --data-urlencode 'ciclo_id=1' \
  --data-urlencode 'armario_id=ARM-01' \
  --data-urlencode 'remessas=REM123\nREM_NOT_FOUND' \
  --data-urlencode 'csrf_token=SEU_CSRF_TOKEN'
```

Exemplo PowerShell (Windows):

```powershell
$cookie = 'PHPSESSID=SEU_SESSION_ID'
$body = @{
  action = 'compare_armario'
  ciclo_id = '1'
  armario_id = 'ARM-01'
  remessas = "REM123`nREM_NOT_FOUND"
  csrf_token = 'SEU_CSRF_TOKEN'
}
Invoke-RestMethod -Uri 'http://localhost/router_public.php?url=inventario/conciliacao-api' -Method POST -Headers @{Cookie=$cookie} -Body $body
```

Notas:
- Ajuste `localhost` para o host correto.
- Para testes repetíveis, crie um usuário de teste e roteie login via API para obter `PHPSESSID` e `csrf_token` programaticamente.
- Se sua aplicação usar proteção adicional (CORS, SameSite cookies, etc.), execute o `curl` a partir do mesmo host de teste ou use uma sessão de navegador automatizada (puppeteer/playwright).
