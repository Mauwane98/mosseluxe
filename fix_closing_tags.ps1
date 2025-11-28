# Remove closing PHP tags from all include files
$files = Get-ChildItem -Path "includes" -Filter "*.php" -Recurse
$count = 0
$modified = 0

foreach ($file in $files) {
    $count++
    $content = Get-Content $file.FullName -Raw
    
    if ($content -match '\?>\s*$') {
        Write-Host "Processing: $($file.Name)"
        $newContent = $content -replace '\?>\s*$', '// No closing PHP tag - prevents accidental whitespace output'
        Set-Content -Path $file.FullName -Value $newContent -NoNewline
        $modified++
        Write-Host "  Removed closing tag" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "Summary:" -ForegroundColor Cyan
Write-Host "Files checked: $count"
Write-Host "Files modified: $modified" -ForegroundColor Green
