# Scans repository for files that are not referenced elsewhere
# Writes JSON array to scripts/unreferenced_candidates.json

$root = Get-Location
$excludeSubstrings = @('_OLD_FILES', '.git', 'node_modules', 'vendor')
$allFiles = Get-ChildItem -Path $root -Recurse -File | Select-Object -ExpandProperty FullName

# Remove paths that contain one of the exclude substrings
$allFiles = $allFiles | Where-Object {
    $full = $_
    -not ($excludeSubstrings | ForEach-Object { if ($full -like "*$_*") { return $true } else { $false } } | Where-Object { $_ } )
}

# Pre-build for performance
$allFilesList = $allFiles

$candidates = @()
$total = $allFilesList.Count
$counter = 0
Write-Host "Scanning $total files for references..."

foreach ($file in $allFilesList) {
    $counter++
    if ($counter % 50 -eq 0) { Write-Host "Checked $counter / $total" }
    $basename = [IO.Path]::GetFileName($file)
    # Search for basename occurrences across all files
    try {
        $results = Select-String -Path $allFilesList -Pattern ([regex]::Escape($basename)) -SimpleMatch -ErrorAction SilentlyContinue
    } catch {
        $results = @()
    }
    if (-not $results) {
        $candidates += $file
        continue
    }
    # Exclude matches that are only in the file itself
    $otherMatches = $results | Where-Object { $_.Path -ne $file }
    if (-not $otherMatches) {
        $candidates += $file
    }
}

$outPath = Join-Path $root "scripts/unreferenced_candidates.json"
$candidates | ConvertTo-Json -Depth 3 | Out-File -FilePath $outPath -Encoding UTF8

Write-Host "Scan complete. Candidates: $($candidates.Count). Written to $outPath"
return 0
