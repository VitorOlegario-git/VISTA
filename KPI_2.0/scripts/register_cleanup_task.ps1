<#
PowerShell script para registrar uma tarefa agendada que executa o script PHP
scripts/cleanup_expired_tokens.php regularmente.

Uso (exemplo):
  .\register_cleanup_task.ps1 -Hours 24 -Notify -Time "03:00" -TaskName "KPI_RemoveExpiredTokens"

Execute este script em uma sessão PowerShell com privilégios de administrador.
#>
param(
    [int]$Hours = 24,
    [switch]$Notify,
    [string]$Time = '03:00',
    [string]$TaskName = 'KPI_RemoveExpiredTokens'
)

Write-Output "Registrando tarefa agendada: $TaskName (horas=$Hours, notify=$Notify, time=$Time)"

$phpCmd = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $phpCmd) {
    Write-Error "PHP não encontrado no PATH. Instale PHP CLI ou execute este script especificando o caminho para php.exe.";
    exit 1
}

# Resolve caminho do script cleanup
$scriptPath = Resolve-Path "$PSScriptRoot\cleanup_expired_tokens.php" -ErrorAction SilentlyContinue
if (-not $scriptPath) {
    Write-Error "Não foi possível localizar scripts/cleanup_expired_tokens.php a partir de $PSScriptRoot";
    exit 1
}

$notifyArg = $Notify.IsPresent ? '--notify' : ''
$tr = "`"$phpCmd`" -f `"$scriptPath`" --hours=$Hours $notifyArg"

# Criar/atualizar tarefa usando schtasks (compatível com Windows 7+)
$createCmd = "schtasks /Create /SC DAILY /TN `"$TaskName`" /TR `"$tr`" /ST $Time /F"
Write-Output "Executando: $createCmd"
Invoke-Expression $createCmd
if ($LASTEXITCODE -eq 0) {
    Write-Output "Tarefa agendada registrada com sucesso: $TaskName"
} else {
    Write-Error "Falha ao registrar a tarefa agendada. Código de saída: $LASTEXITCODE"
}
