# Remove closing PHP tags from all include files
# This prevents whitespace output that breaks JSON responses

$files = Get-ChildItem -Path "includes" -Filter "*.php" -Recurse

$count = 0
$modified = 0

foreach ($file in $files) {
    $count++
    $content = Get-Content $file.FullName -Raw
    
    # Check if file has closing tag with optional whitespace after
    if ($content -match '\?>\s*$') {
        Write-Host "Processing: $($file.Name)"
        
        # Remove closing tag and trailing whitespace
        $newContent = $content -replace '\?>\s*$', '// No closing PHP tag - prevents accidental whitespace output'
        
        # Save the file
        Set-Content -Path $file.FullName -Value $newContent -NoNewline
        
        $modified++
        Write-Host "  ✓ Removed closing tag" -ForegroundColor Green
    }
}

Write-Host "`nSummary:" -ForegroundColor Cyan
Write-Host "  Files checked: $count"
Write-Host "  Files modified: $modified" -ForegroundColor Green
Write-Host "`nDone! All closing PHP tags removed from includes" -ForegroundColor Green
