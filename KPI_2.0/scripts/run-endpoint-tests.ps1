Param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [string]$Cookie = '',
    [string]$CsrfToken = '',
    [switch]$SkipStart,
    [switch]$SaveResponses
)

# Smoke test runner for VISTA KPI 2.0
# Usage: run this from the repository root in PowerShell. If PHP is available the script will start the built-in server.

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectRoot

if (-not (Test-Path -Path "logs")) { New-Item -Path "logs" -ItemType Directory | Out-Null }
$logFile = Join-Path $projectRoot 'logs\endpoint-tests.log'
"--- Endpoint tests run: $(Get-Date -Format o) ---" | Out-File -FilePath $logFile -Encoding utf8 -Append

# Try to start PHP built-in server if available; if not, continue (user can run server manually)
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
$proc = $null
if (-not $SkipStart) {
    if ($phpCmd) {
        Write-Host "Starting PHP built-in server for local testing..." -ForegroundColor Cyan
        # Attempt to extract host/port from BaseUrl when it's a simple http(s)://host:port
        $uri = [uri]$BaseUrl
        $phpArgs = "-S $($uri.Host)`:$($uri.Port) router_public.php"
        $proc = Start-Process -FilePath $phpCmd.Path -ArgumentList $phpArgs -PassThru -WindowStyle Hidden -WorkingDirectory $projectRoot
        Start-Sleep -Seconds 1
    } else {
        Write-Host "PHP not in PATH — skipping auto-start. Ensure your server is running at $BaseUrl" -ForegroundColor Yellow
    }
} else {
    Write-Host "Skipping PHP auto-start as requested. Using BaseUrl: $BaseUrl" -ForegroundColor Yellow
}

function Log-Request($label, $responseText, $fullResponse = $null, $path = '') {
    "[$(Get-Date -Format o)] $label" | Out-File -FilePath $logFile -Encoding utf8 -Append
    $responseText | Out-File -FilePath $logFile -Encoding utf8 -Append
    "" | Out-File -FilePath $logFile -Encoding utf8 -Append

    if ($SaveResponses -and $fullResponse) {
        # sanitize path for filename
        $safe = $path -replace '[\\/:?"<>|& ]','_' -replace '&','and'
        if ([string]::IsNullOrEmpty($safe)) { $safe = (Get-Date -Format 'yyyyMMddHHmmss') }
        $outFile = Join-Path $projectRoot ("logs/response_$safe.txt")
        $fullResponse | Out-File -FilePath $outFile -Encoding utf8
        Write-Host "Saved full response to $outFile" -ForegroundColor DarkCyan
    }
}

function Invoke-Check($path, $method = 'GET', $body = $null) {
    $url = $BaseUrl.TrimEnd('/') + $path
    try {
        $headers = @{}
        if ($Cookie -ne '') { $headers['Cookie'] = $Cookie }
        if ($method -eq 'GET') {
            $r = Invoke-WebRequest -UseBasicParsing -Uri $url -Method GET -TimeoutSec 15 -Headers $headers -ErrorAction Stop
        } else {
            if ($body -eq $null) { $body = @{} }
            if ($CsrfToken -ne '' -and -not $body.ContainsKey('csrf_token')) { $body['csrf_token'] = $CsrfToken }
            $r = Invoke-WebRequest -UseBasicParsing -Uri $url -Method POST -Body $body -TimeoutSec 15 -Headers $headers -ErrorAction Stop
        }
        $status = $r.StatusCode
        $content = $r.Content
    } catch {
        $ex = $_.Exception
        if ($ex.Response -ne $null) {
            try { $status = $ex.Response.StatusCode.value__ } catch { $status = 'ERR' }
            try {
                $sr = New-Object System.IO.StreamReader($ex.Response.GetResponseStream())
                $content = $sr.ReadToEnd()
            } catch { $content = $ex.Message }
        } else {
            $status = 'ERR'
            $content = $ex.Message
        }
    }
    $snippet = if ($null -eq $content) { '' } else { $content.Substring(0, [Math]::Min(500, $content.Length)) }
    Write-Host "[$status] $url"
    if ($snippet -ne '') { Write-Host "Response snippet:`n$snippet`n" }
    Log-Request "$status $url" $snippet $content $path

    # Additional validation for inventario-api JSON/401
    if ($path -like '/inventario-api*') {
        try {
            $j = $null
            if ($content -and $content.Trim().StartsWith('{')) { $j = ConvertFrom-Json $content -ErrorAction Stop }
            if ($status -eq 401 -or ($j -and $j.error)) {
                Write-Host "-> inventario-api responded with 401/unauthorized or error: $($j.error)" -ForegroundColor Yellow
            } elseif ($j -and $j.success) {
                Write-Host "-> inventario-api returned success (items: $($j.data.items.Count) if present)" -ForegroundColor Green
            } else {
                Write-Host "-> inventario-api response parsed but no success flag found" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "-> inventario-api response is not valid JSON" -ForegroundColor Red
        }
    }
}

try {
    # Run targeted tests
    Invoke-Check '/inventario-api?action=list&armario=ARM-01'
    Invoke-Check '/inventario'
    Invoke-Check '/favicon.ico'

    Write-Host "Smoke tests completed. See logs/endpoint-tests.log for details." -ForegroundColor Green
} finally {
    if ($proc -and -not $proc.HasExited) {
        Write-Host "Stopping PHP server (PID $($proc.Id))..." -ForegroundColor Cyan
        try { Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue } catch {}
    }
}
