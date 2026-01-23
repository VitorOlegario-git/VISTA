# Endpoint smoke tests

This script helps run simple smoke tests against the project using PHP built-in server.

Prerequisites
- PHP installed and available on PATH
- (Optional) Database and `.env` configured if you want to test DB-backed endpoints

Run
1. Open PowerShell and change to the project root (where `router_public.php` is located).
2. Run:

```powershell
.\scripts\run-endpoint-tests.ps1
```

Output
- Results are appended to `logs/endpoint-tests.log`.

Notes
- Some endpoints (e.g., `consulta/id`) may return 500 or empty responses unless the DB and `.env` are configured.
- If PHP is not installed, install it (https://www.php.net/downloads) or use WSL with PHP available.
